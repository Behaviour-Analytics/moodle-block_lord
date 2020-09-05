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
 * This file displays the documentation for Learning Object Relation Discovery (LORD).
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT);

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);
require_capability('block/lord:view', $context);

// Set up the page.
$PAGE->set_url('/blocks/lord/documentation.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_lord'));

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($course->fullname);

// Output page.
echo $OUTPUT->header();

// Make the hyperlink menu.
$cid = array('id' => $COURSE->id);

echo html_writer::tag('a', get_string('docs:whatis', 'block_lord'), array(
    'href' => new moodle_url('/blocks/lord/documentation.php#whatis', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docs:howto', 'block_lord'), array(
    'href' => new moodle_url('/blocks/lord/documentation.php#howto', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docs:settings', 'block_lord'), array(
    'href' => new moodle_url('/blocks/lord/documentation.php#settings', $cid)));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// What is LORD?
echo html_writer::div(get_string('docs:whatis', 'block_lord'), 'bigger', array('id' => 'whatis'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:whatis:desc1', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// How to use LORD.
echo html_writer::div(get_string('docs:howto', 'block_lord'), 'bigger', array('id' => 'howto'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:howto:desc1', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:howto:desc2', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:howto:desc3', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:howto:desc4', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// How to configure LORD.
echo html_writer::div(get_string('docs:settings', 'block_lord'), 'bigger', array('id' => 'settings'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:settings:desc1', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:settings:desc2', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:settings:desc3', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:settings:desc4', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docs:settings:desc5', 'block_lord'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

echo $OUTPUT->footer();
