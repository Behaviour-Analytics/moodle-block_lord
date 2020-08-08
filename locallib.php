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
 * Library of functions and classes for Learning Object Relation Discovery (LORD).
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Called to get the module information for a course.
 *
 * @param stdClass $course The DB course table record
 * @return array
 */
function block_lord_get_course_info(&$course) {

    // Get the course module information.
    $modinfo = get_fast_modinfo($course);
    $courseinfo = [];

    foreach ($modinfo->sections as $sectionnum => $section) {

        foreach ($section as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Only want clickable modules.
            if (!$cm->has_view() || !$cm->uservisible) {
                continue;
            }

            $courseinfo[] = array(
                'id'     => $cmid,
                'entype' => $cm->modname,
                'type'   => get_string('modulename', $cm->modname),
                'name'   => $cm->name,
                'sect'   => $sectionnum
            );
        }
    }

    return $courseinfo;
}

/**
 * Function to get the current similarity values from the DB.
 *
 * @param stdClass $course The course object.
 * @return array
 */
function block_lord_get_scores(&$course) {
    global $DB;

    $scores = [];
    $matrices = [];
    $current = null;
    $values = [];
    $mats = [];

    $records = $DB->get_records('block_lord_comparisons', ['courseid' => $course->id], 'module1, module2');

    foreach ($records as $record) {

        $key = $record->module1 . '_' . $record->module2;

        if ($key != $current) {
            if ($current) {
                $scores[$current] = $values;
                $matrices[$current] = $mats;
            }
            $current = $key;
            $values = [];
            $mats = [];
        }

        if ($record->value) {
            $values[$record->compared] = $record->value;
            $mats[$record->compared] = json_decode($record->matrix);
        }
    }

    // Include last set of records.
    if ($current) {
        $scores[$current] = $values;
        $matrices[$current] = $mats;
    }

    return [$scores, $matrices];
}

/**
 * Function to get the current progress values for the table.
 *
 * @param stdClass $course The course object.
 * @return array
 */
function block_lord_get_progress(&$course) {
    global $DB;

    $comparisons = [];
    $calculated = 0;
    $errors = 0;

    $records = $DB->get_records('block_lord_comparisons', ['courseid' => $course->id]);

    foreach ($records as $record) {

        $key = $record->module1 . '_' . $record->module2;

        if (isset($comparisons[$key])) {
            continue;
        }
        $comparisons[$key] = 1;

        if ($record->value) {
            $calculated++;
        }

        if ($record->value != '' && $record->value == 0.0) {
            $errors++;
        }
    }

    $total = count($comparisons);
    $percent = $total == 0 ? 0 : round($calculated / $total * 100, 2);
    $progress = array(
        'total'      => $total,
        'calculated' => $calculated,
        'percent'    => $percent,
        'errors'     => $errors
    );

    return $progress;
}

/**
 * Function to get the text content for the modules.
 *
 * @param stdClass $course The course object.
 * @return array
 */
function block_lord_get_contents(&$course) {
    global $DB;

    $dictionary = block_lord_get_dictionary();

    // Get the module names and introductions.
    $records = $DB->get_records('block_lord_modules', ['courseid' => $course->id]);
    $names = [];
    foreach ($records as $record) {

        // Split intro into sentences.
        $split = block_lord_split_paragraph($record->intro);
        $intros = [];
        foreach ($split as $intro) {
            $intros[] = block_lord_clean_sentence($intro, $dictionary);
        }

        $names[$record->module] = array(
            'name'   => $record->name,
            'intro'  => $record->intro,
            'cname'  => block_lord_clean_sentence($record->name, $dictionary),
            'cintro' => $intros
        );
    }
    unset($record);

    // Get the module paragraph contents.
    $records = $DB->get_records('block_lord_paragraphs', ['courseid' => $course->id], 'module, paragraph');
    $paragraphs = [];
    $paras = [];
    $module = null;

    foreach ($records as $record) {
        if ($module != $record->module) {
            if ($module) {
                $paragraphs[$module] = $paras;
            }
            $module = $record->module;
            $paras = [];
        }
        $paras[$record->paragraph] = $record->content;
    }

    // Include last set of records.
    if ($module) {
        $paragraphs[$module] = $paras;
    }
    unset($paras);
    unset($module);

    // Split the paragraphs into cleaned sentences.
    $sentences = [];
    foreach ($paragraphs as $module => $paras) {
        $sentences[$module] = [];
        foreach ($paras as $para) {
            $sents = block_lord_split_paragraph($para);
            $ss = [];
            foreach ($sents as $sent) {
                $ss[] = block_lord_clean_sentence($sent, $dictionary);
            }
            $sentences[$module][] = $ss;
        }
    }

    return [$names, $paragraphs, $sentences];
}

/**
 * Function to split a paragraph into sentences. Look ahead/behind is used in
 * the regex to ignore honorific titles, ensure punctuation is followed by
 * whitespace, and the next sentence starts with a capital letter. Abreviations
 * are ignored as split points, unless they are followed by whitespace and a
 * capital letter. Regex was adapted from
 * https://stackoverflow.com/questions/10494176/explode-a-paragraph-into-sentences-in-php.
 *
 * @param string $paragraph The paragraph to split.
 * @return array
 */
function block_lord_split_paragraph(&$paragraph) {

    $regex = '/(?<!Mr.|Mrs.|Ms.|Mx.|Dr.|Prof.|Pr.|Br.|Sr.|Fr.|Rev.)(?<=[.?!;])\s+(?=[A-Z])/';
    $sentences = preg_split($regex, $paragraph, -1, PREG_SPLIT_NO_EMPTY);

    return $sentences;
}

/**
 * Simple function to return the regular expression used in cleaning
 * non-alphanumeric characters from a sentence. Defined once, used twice.
 *
 * @return string
 */
function block_lord_get_cleaning_regex() {

    return '/[\.\,\:\;\?\!\(\)\@\#\$\%\&\*\-\_\=\+\[\]\{\}\<\>\^\"\\n\\r\/]+/';
}

/**
 * Function to remove stop words, numbers, words not in Wordnet, and
 * duplicate words from a sentence.
 *
 * @param string $sentence The sentence to clean.
 * @param string $dictionary A list of words and their status in Wordnet.
 * @return string
 */
function block_lord_clean_sentence(&$sentence, &$dictionary) {

    $regex = block_lord_get_cleaning_regex();
    $nopunctuation = preg_replace($regex, ' ', $sentence);
    $lowercase = strtolower($nopunctuation);
    $words = explode(' ', $lowercase);
    $cleaned = '';
    $seen = [];

    foreach ($words as $word) {

        if (isset($seen[$word])) { // Ignore duplicate words.
            continue;
        }
        $seen[$word] = $word;

        if (strlen($word) == 0 || is_numeric($word)) { // Ignore numbers.
            continue;
        }

        if (isset($dictionary[$word]) && $dictionary[$word] == 0) {
            $cleaned .= $word . ' ';
        }
    }

    return trim($cleaned);
}

/**
 * Function to build the Wordnet dictionary.
 *
 * @return array
 */
function block_lord_get_dictionary() {
    global $DB;

    $dictionary = [];
    $records = $DB->get_records('block_lord_dictionary');

    foreach ($records as $record) {
        $dictionary[$record->word] = $record->status;
    }

    return $dictionary;
}

/**
 * Function to retrieve the similarity comparison weights.
 *
 * @param stdClass $course The course object.
 * @return array
 */
function block_lord_get_comparison_weights(&$course) {
    global $DB;

    $weights = array(
        'names' => 1.0,
        'intros' => 1.0,
        'sentences' => 1.0
    );

    $record = $DB->get_record('block_lord_max_words', ['courseid' => $course->id]);
    if ($record) {
        $weights['names'] = $record->nameweight;
        $weights['intros'] = $record->introweight;
        $weights['sentences'] = $record->sentenceweight;
    }

    return $weights;
}

/**
 * Form definition for the custom settings and reset options.
 *
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lord_reset_form extends moodleform {

    /**
     * Function to make the form.
     */
    public function definition() {
        global $DB, $COURSE;

        // Get custom settings, if exist.
        $record = $DB->get_record('block_lord_max_words', ['courseid' => $COURSE->id]);

        if ($record) {
            $started = $record->dodiscovery;
            $maxlength = $record->maxlength;
            $maxsentence = $record->maxsentence;
            $maxparas = $record->maxparas;
            $nameweight = $record->nameweight;
            $introweight = $record->introweight;
            $sentenceweight = $record->sentenceweight;

        } else { // Defaults.
            $started = 0;
            $maxlength = 32;
            $maxsentence = 3;
            $maxparas = 3;
            $nameweight = 1.0;
            $introweight = 1.0;
            $sentenceweight = 1.0;
        }

        $mform = &$this->_form;

        // Course id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'config_header', get_string('generalheader', 'block_lord'));

        $options = array('yes' => get_string('yes'), 'no' => get_string('no'));

        // Option to start/resume discovery process.
        $mform->addElement('advcheckbox', 'start_discovery', get_string('startdiscovery', 'block_lord'),
            get_string('startdiscoverylabel', 'block_lord'));
        $mform->setDefault('start_discovery', $started);

        // Text entry box for maximum number of paragraphs.
        $mform->addElement('text', 'num_paras', get_string('numparas', 'block_lord'));
        $mform->setType('num_paras', PARAM_INT);
        $mform->addRule('num_paras', get_string('formerror', 'block_lord'),
            'regex', '/^[1-9][0-9]*$/', 'client', true);
        $mform->setDefault('num_paras', $maxparas);

        // Text entry box for maximum number of sentences.
        $mform->addElement('text', 'num_sentence', get_string('numsentence', 'block_lord'));
        $mform->setType('num_sentence', PARAM_INT);
        $mform->addRule('num_sentence', get_string('formerror', 'block_lord'),
            'regex', '/^[1-9][0-9]*$/', 'client', true);
        $mform->setDefault('num_sentence', $maxsentence);

        // Text entry box for maximum number of words.
        $mform->addElement('text', 'num_words', get_string('numwords', 'block_lord'));
        $mform->setType('num_words', PARAM_INT);
        $mform->addRule('num_words', get_string('formerror', 'block_lord'),
            'regex', '/^[1-9][0-9]*$/', 'client', true);
        $mform->setDefault('num_words', $maxlength);

        // Text entry box for name comparison weight.
        $mform->addElement('text', 'name_weight', get_string('nameweight', 'block_lord'));
        $mform->setType('name_weight', PARAM_FLOAT);
        $mform->addRule('name_weight', get_string('formerror2', 'block_lord'),
            'regex', '/^[0-9]?\.?[0-9]{0,2}$/', 'client', true);
        $mform->setDefault('name_weight', $nameweight);

        // Text entry box for intro comparison weight.
        $mform->addElement('text', 'intro_weight', get_string('introweight', 'block_lord'));
        $mform->setType('intro_weight', PARAM_FLOAT);
        $mform->addRule('intro_weight', get_string('formerror2', 'block_lord'),
            'regex', '/^[0-9]?\.?[0-9]{0,2}$/', 'client', true);
        $mform->setDefault('intro_weight', $introweight);

        // Text entry box for sentence comparison weight.
        $mform->addElement('text', 'sentence_weight', get_string('sentenceweight', 'block_lord'));
        $mform->setType('sentence_weight', PARAM_FLOAT);
        $mform->addRule('sentence_weight', get_string('formerror2', 'block_lord'),
            'regex', '/^[0-9]?\.?[0-9]{0,2}$/', 'client', true);
        $mform->setDefault('sentence_weight', $sentenceweight);

        $mform->addElement('header', 'config_header', get_string('resetheader2', 'block_lord'));

        // Yes/No select option for resetting comparison errors.
        $mform->addElement('select', 'reset_comparisons', get_string('resetcomparisons', 'block_lord'), $options);
        $mform->getElement('reset_comparisons')->setSelected('no');

        // Yes/No select option for resetting comparison errors.
        $mform->addElement('select', 'reset_content', get_string('resetcontent', 'block_lord'), $options);
        $mform->getElement('reset_content')->setSelected('no');

        // Options to add or remove stop words.
        $mform->addElement('header', 'config_header', get_string('stopwordsheader', 'block_lord'));

        // Get the current stop words and add to form.
        $records = $DB->get_records('block_lord_dictionary', ['status' => 2], 'word');
        $options = [null];
        foreach ($records as $record) {
            $options[$record->word] = $record->word;
        }

        $words = str_replace(' ', ', ', get_string('stopwords', 'block_lord'));
        $desc = get_string('removestopword', 'block_lord', $words);

        $mform->addElement('select', 'stopwords', $desc, $options);

        // Text entry for adding a new stop word.
        $mform->addElement('text', 'add_word', get_string('addstopword', 'block_lord'));
        $mform->setType('add_word', PARAM_RAW);

        $this->add_action_buttons();
    }
}
