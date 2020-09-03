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
 * This script is for debugging commparison errors.
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
$PAGE->set_url('/blocks/lord/debug.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_lord'));

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($course->fullname);

// Output page.
echo $OUTPUT->header();

$keys = [];
$mods = [];

// Get the errored DB entries with module numbers and comparison.
$records = $DB->get_records('block_lord_comparisons', ['courseid' => $course->id, 'value' => 0.0]);
foreach ($records as $record) {

    $key = $record->module1 . '_' . $record->module2;

    $keys[$key] = [$record->module1, $record->module2, $record->compared];
    $mods[$record->module1] = $record->module1;
    $mods[$record->module2] = $record->module2;
}

if (count($records) > 0) { // There are errors to process.

    // Get name and intro data.
    list($insql, $inparams) = $DB->get_in_or_equal($mods);
    $sql = "SELECT * FROM {block_lord_modules} WHERE module $insql";
    $namesandintros = $DB->get_records_sql($sql, $inparams);

    $names = [];
    $intros = [];
    foreach ($namesandintros as $ni) {
        $names[$ni->module] = $ni->name;
        $intros[$ni->module] = $ni->intro;
    }

    // Get paragraph content data.
    $sql = "SELECT * FROM {block_lord_paragraphs} WHERE module $insql ORDER BY module, paragraph";
    $paragraphs = $DB->get_records_sql($sql, $inparams);

    $paras = [];
    foreach ($paragraphs as $p) {
        if (!isset($paras[$p->module])) {
            $paras[$p->module] = [];
        }
        $paras[$p->module][$p->paragraph] = block_lord_split_paragraph($p->content);
    }

    // Need dictionary for cleaning sentences.
    $dict = block_lord_get_dictionary();

    // Show the error data. Might be name, intro, or paragraph data.
    unset($key);
    foreach ($keys as $key => $value) {
        echo html_writer::div($key.' '.$value[2]);

        if ($value[2] == 'name') {
            echo html_writer::div($value[0].': '.$names[$value[0]].' => '.block_lord_clean_sentence($names[$value[0]], $dict));
            echo html_writer::div($value[1].': '.$names[$value[1]].' => '.block_lord_clean_sentence($names[$value[1]], $dict));

        } else if (substr($value[2], 0, 5) == 'intro') {
            $is = preg_split('/x/', substr($value[2], 5));
            $s0 = block_lord_split_paragraph($intros[$value[0]])[$is[0]];
            $s1 = block_lord_split_paragraph($intros[$value[1]])[$is[1]];
            echo html_writer::div($value[0].' '.$is[0].': '.$s0.' => '.block_lord_clean_sentence($s0, $dict));
            echo html_writer::div($value[1].' '.$is[1].': '.$s1.' => '.block_lord_clean_sentence($s1, $dict));

        } else {
            $ps = preg_split('/P/', $value[2]);
            $ps0 = preg_split('/S/', $ps[1]);
            $ps1 = preg_split('/S/', $ps[2]);
            $s0 = $paras[$value[0]][$ps0[0]][$ps0[1]];
            $s1 = $paras[$value[1]][$ps1[0]][$ps1[1]];
            echo html_writer::div($value[0].' '.$ps[1].': '.$s0.' => '.block_lord_clean_sentence($s0, $dict));
            echo html_writer::div($value[1].' '.$ps[2].': '.$s1.' => '.block_lord_clean_sentence($s1, $dict));
        }

        echo html_writer::empty_tag('br');
    }
}

echo html_writer::div('Errors: '.count($records));

echo $OUTPUT->footer();
