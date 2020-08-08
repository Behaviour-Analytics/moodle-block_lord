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
 * Block lord is defined here.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/blocks/lord/locallib.php");

/**
 * Lord block.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lord extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_lord');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        // Do not show block for student users.
        $context = context_course::instance($COURSE->id);
        if (!has_capability('block/lord:view', $context)) {
            return null;
        }

        // Do not show block if not configured to show.
        if (!get_config('block_lord', 'showblock')) {
            return null;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        // Link to view the graphing page.
        $this->content->text = html_writer::tag('a', get_string("launch", "block_lord"),
            array('href' => new moodle_url('/blocks/lord/view.php', array(
                'id' => $COURSE->id
            ))));
        $this->content->text .= html_writer::empty_tag('br');
        $this->content->text .= html_writer::empty_tag('br');

        // Get some data for this course.
        $progress = block_lord_get_progress($COURSE);
        $courseinfo = block_lord_get_course_info($COURSE);

        // Build the progress table.
        $table = new html_table();
        $data = [];

        $table->head = array(
            get_string('progresstitle', 'block_lord'),
        );
        $table->headspan = [2];

        $data[] = new html_table_row(array(
            new html_table_cell(html_writer::div(get_string('learningactivities', 'block_lord'))),
            new html_table_cell(html_writer::div(count($courseinfo))),
        ));

        $data[] = new html_table_row(array(
            new html_table_cell(html_writer::div(get_string('connections', 'block_lord'))),
            new html_table_cell(html_writer::div($progress['total'])),
        ));

        $data[] = new html_table_row(array(
            new html_table_cell(html_writer::div(get_string('complete', 'block_lord'))),
            new html_table_cell(html_writer::div($progress['calculated'])),
        ));

        $data[] = new html_table_row(array(
            new html_table_cell(html_writer::div(get_string('percent', 'block_lord'))),
            new html_table_cell(html_writer::div($progress['percent'] . '%'))
        ));

        $data[] = new html_table_row(array(
            new html_table_cell(html_writer::div(get_string('errors', 'block_lord'))),
            new html_table_cell(html_writer::div($progress['errors']))
        ));

        // Align text in all table cells.
        foreach ($data as $row) {
            foreach ($row->cells as $cell) {
                $cell->style = 'vertical-align: middle; ';
            }
        }

        $table->data = $data;
        $this->content->text .= html_writer::table($table);

        // Link for custom settings page.
        $this->content->text .= html_writer::tag('a', get_string("settingspage", "block_lord"),
            array('href' => new moodle_url('/blocks/lord/custom_settings.php', array(
                'id' => $COURSE->id
            ))));

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_lord');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return array Array of pages and permissions.
     */
    public function applicable_formats() {

        return array('all' => false, 'course-view' => true);
    }

    /**
     * Ensure global settings are available.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}
