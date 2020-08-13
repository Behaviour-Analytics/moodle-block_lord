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
 * This script controls the viewing of the network graph.
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

// Get some data.
list($scores, $matrices) = block_lord_get_scores($course);
list($names, $paragraphs, $sentences) = block_lord_get_contents($course);

// Build outgoing data.
$out = [];
$out['courseid'] = $course->id;
$out['sesskey'] = sesskey();
$out['weights'] = $scores;
$out['matrices'] = $matrices;
$out['modules'] = block_lord_get_course_info($course);
$out['coordsscript'] = (string) new moodle_url('/blocks/lord/save-graph.php');
$out['comparisonweights'] = block_lord_get_comparison_weights($course);
$out['names'] = $names;
$out['paragraphs'] = $paragraphs;
$out['sentences'] = $sentences;
$out['presetnodes'] = block_lord_get_node_coords($course);

// Set up the page.
$PAGE->set_url('/blocks/lord/view.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_lord'));

// JavaScript.
$PAGE->requires->js_call_amd('block_lord/modules', 'init');
$PAGE->requires->js_init_call('waitForModules', array($out), true);
$PAGE->requires->js('/blocks/lord/javascript/main.js');

$PAGE->requires->string_for_js('custombutton', 'block_lord');
$PAGE->requires->string_for_js('systembutton', 'block_lord');
$PAGE->requires->string_for_js('resetbutton', 'block_lord');
$PAGE->requires->string_for_js('graphsaved', 'block_lord');
$PAGE->requires->string_for_js('systemgraph', 'block_lord');
$PAGE->requires->string_for_js('usergraph', 'block_lord');
$PAGE->requires->string_for_js('similaritystr', 'block_lord');
$PAGE->requires->string_for_js('comparisonerror', 'block_lord');
$PAGE->requires->string_for_js('notcalculated', 'block_lord');
$PAGE->requires->string_for_js('section', 'block_lord');
$PAGE->requires->string_for_js('mindistance', 'block_lord');
$PAGE->requires->string_for_js('maxdistance', 'block_lord');
$PAGE->requires->string_for_js('scalingfactor', 'block_lord');
$PAGE->requires->string_for_js('name', 'block_lord');
$PAGE->requires->string_for_js('intro', 'block_lord');
$PAGE->requires->string_for_js('moduleid', 'block_lord');
$PAGE->requires->string_for_js('optimalassign', 'block_lord');
$PAGE->requires->string_for_js('names', 'block_lord');
$PAGE->requires->string_for_js('introscost', 'block_lord');
$PAGE->requires->string_for_js('intros', 'block_lord');
$PAGE->requires->string_for_js('parascost', 'block_lord');
$PAGE->requires->string_for_js('sentscost', 'block_lord');

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($course->fullname);

// Output page.
echo $OUTPUT->header();

// Div for network graph.
echo html_writer::div('', '', array('id' => 'graph'));
echo html_writer::empty_tag('br');

// Div for "Save graph" button.
echo html_writer::div('', '', array('id' => 'save-graph'));
echo html_writer::empty_tag('br');

// Table for displaying module content.
$table = new html_table();

$headcell = new html_table_cell(html_writer::div('&nbsp', '', array('id' => 'similarity-score')));
$headcell->style = 'text-align: center;';
$table->head = [$headcell];
$table->headspan = [2];
$table->size = ['50%', '50%'];
$table->data = [];

$table->data[] = new html_table_row(array(
    new html_table_cell(html_writer::div('&nbsp', '', array('id' => 'node-content-left'))),
    new html_table_cell(html_writer::div('&nbsp', '', array('id' => 'node-content-right'))),
));
$cell = new html_table_cell(html_writer::div('&nbsp', '', array('id' => 'similarity-matrix')));
$cell->colspan = 2;
$table->data[] = new html_table_row(array($cell));

echo html_writer::table($table);

echo $OUTPUT->footer();
