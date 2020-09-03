<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Various custom settings and reset options.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once("$CFG->dirroot/blocks/lord/locallib.php");

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT);

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);
require_capability('block/lord:view', $context);

// Set up the page.
$PAGE->set_url('/blocks/lord/custom_settings.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_lord'));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($course->fullname);

// The options form.
$mform = new block_lord_reset_form();

$toform = ['id' => $course->id];
$mform->set_data($toform);

// Main course page URL for redirects.
$url = new moodle_url('/course/view.php', ['id' => $COURSE->id]);

// Handle cancelled form.
if ($mform->is_cancelled()) {
    redirect($url);

} else if ($fromform = $mform->get_data()) {
    // Handle submitted form.

    // Get custom settings, if exist.
    $record = $DB->get_record('block_lord_max_words', ['courseid' => $course->id]);

    $params = array(
        'courseid' => $course->id,
        'dodiscovery' => intval($fromform->start_discovery),
        'maxlength' => intval($fromform->num_words),
        'maxsentence' => intval($fromform->num_sentence),
        'maxparas' => intval($fromform->num_paras),
        'nameweight' => floatval($fromform->name_weight),
        'introweight' => floatval($fromform->intro_weight),
        'sentenceweight' => floatval($fromform->sentence_weight),
    );

    // Insert/update custom settings.
    if ($record) {
        $params['id'] = $record->id;
        $DB->update_record('block_lord_max_words', $params);
    } else {
        $DB->insert_record('block_lord_max_words', $params);
    }

    // Reset comparison errors.
    if ($fromform->reset_errors == 'yes') {
        $params = array(
            'courseid' => $course->id,
            'value' => 0.0
        );
        $DB->delete_records('block_lord_comparisons', $params);
    }

    // Reset all comparisons.
    if ($fromform->reset_comparisons == 'yes') {
        $DB->delete_records('block_lord_comparisons', ['courseid' => $course->id]);
    }

    // Reset module content data and comparisons.
    if ($fromform->reset_content == 'yes') {
        $params = array('courseid' => $course->id);
        $DB->delete_records('block_lord_paragraphs', $params);
        $DB->delete_records('block_lord_modules', $params);
        $DB->delete_records('block_lord_comparisons', $params);
    }

    // Remove a stop word from the dictionary.
    if ($fromform->stopwords) {
        $DB->delete_records('block_lord_dictionary', ['word' => $fromform->stopwords]);
    }

    // Add a stop word to the dictionary.
    if ($fromform->add_word && !is_numeric($fromform->add_word)) {
        $word = strtolower($fromform->add_word);

        // If word is already in dictionary, change status.
        $record = $DB->get_record('block_lord_dictionary', ['word' => $word]);
        if ($record) {
            $DB->update_record('block_lord_dictionary', array(
                'id' => $record->id,
                'word' => $record->word,
                'status' => 2
            ));

        } else { // Insert the new word.
            $DB->insert_record('block_lord_dictionary', ['word' => $word, 'status' => 2]);
        }
    }

    redirect($url);

} else {
    // Output form.

    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}

