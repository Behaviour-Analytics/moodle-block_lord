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
 * The upgrade file for Activity Node Lord.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The upgrade function.
 *
 * @param int $oldversion The current version in the database.
 */
function xmldb_block_lord_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020072800) {

        // Define field dodiscovery to be added to block_lord_max_words.
        $table = new xmldb_table('block_lord_max_words');
        $field = new xmldb_field('dodiscovery', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'courseid');

        // Conditionally launch add field dodiscovery.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020072800, 'lord');
    }
    if ($oldversion < 2020080600) {

        // Define field maxdist to be dropped from block_lord_max_words.
        $table = new xmldb_table('block_lord_max_words');
        $field = new xmldb_field('distscale');

        // Conditionally launch drop field maxdist.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('maxdist');

        // Conditionally launch drop field maxdist.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('mindist');

        // Conditionally launch drop field maxdist.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('simtimeout');

        // Conditionally launch drop field maxdist.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020080600, 'lord');
    }

    if ($oldversion < 2020080601) {

        // Remove all data from the coords table before adding fields to avoid errors.
        $DB->delete_records_select('block_lord_coords', 'id > :minid', ['minid' => 0]);

        // Define field userid to be added to block_lord_coords.
        $table = new xmldb_table('block_lord_coords');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('visible', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null, 'ycoord');

        // Conditionally launch add field visible.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020080601, 'lord');
    }

    if ($oldversion < 2020080602) {

        // Remove all data from the coords table before adding fields to avoid errors.
        $DB->delete_records_select('block_lord_scales', 'id > :minid', ['minid' => 0]);
        $DB->delete_records_select('block_lord_coords', 'id > :minid', ['minid' => 0]);

        // Define field userid to be added to block_lord_scales.
        $table = new xmldb_table('block_lord_scales');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020080602, 'lord');
    }

    if ($oldversion < 2020080603) {

        // Changing type of field moduleid on table block_lord_coords to char.
        $table = new xmldb_table('block_lord_coords');
        $field = new xmldb_field('moduleid', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, 'changed');

        // Launch change of type for field moduleid.
        $dbman->change_field_type($table, $field);

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020080603, 'lord');
    }

    if ($oldversion < 2020081100) {

        // Remove all data from the scales table before adding fields to avoid errors.
        $DB->delete_records_select('block_lord_scales', 'id > :minid', ['minid' => 0]);
        $DB->delete_records_select('block_lord_coords', 'id > :minid', ['minid' => 0]);

        // Define field userid to be dropped from block_lord_coords.
        $table = new xmldb_table('block_lord_coords');
        $field = new xmldb_field('userid');

        // Conditionally launch drop field moduleid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field userid to be dropped from block_lord_scales.
        $table = new xmldb_table('block_lord_scales');
        $field = new xmldb_field('userid');

        // Conditionally launch drop field userid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field iscustom to be added to block_lord_scales.
        $table = new xmldb_table('block_lord_scales');
        $field = new xmldb_field('iscustom', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'scale');

        // Conditionally launch add field iscustom.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020081100, 'lord');
    }

    if ($oldversion < 2020081200) {

        // Remove all data from the scales table before adding fields to avoid errors.
        $DB->delete_records_select('block_lord_scales', 'id > :minid', ['minid' => 0]);
        $DB->delete_records_select('block_lord_coords', 'id > :minid', ['minid' => 0]);

        // Define field mindist to be added to block_lord_scales.
        $table = new xmldb_table('block_lord_scales');
        $field = new xmldb_field('mindist', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null, 'iscustom');

        // Conditionally launch add field mindist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('maxdist', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'mindist');

        // Conditionally launch add field mindist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('distscale', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null, 'maxdist');

        // Conditionally launch add field mindist.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lord savepoint reached.
        upgrade_block_savepoint(true, 2020081200, 'lord');
    }

    if ($oldversion < 2020090201) {

        // Remove all data from the other tables to avoid errors.
        $DB->delete_records_select('block_lord_scales', 'id > :minid', ['minid' => 0]);
        $DB->delete_records_select('block_lord_coords', 'id > :minid', ['minid' => 0]);

        // Define block_lord_links table.
        $table = new xmldb_table('block_lord_links');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('coordsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module1', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('module2', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('weight', XMLDB_TYPE_NUMBER, '20,10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_lord_links.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_lord_links.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Behaviour savepoint reached.
        upgrade_block_savepoint(true, 2020090201, 'lord');
    }

    return true;
}