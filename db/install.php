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
 * The install file for LORD.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add stop word to the dictionary so it is populated before use.
 */
function xmldb_block_lord_install() {
    global $DB;

    $words = get_string('stopwords', 'block_lord');
    $words = explode(' ', $words);

    $params = [];
    foreach ($words as $word) {
        $params[] = (object) array(
            'word' => $word,
            'status' => 2
        );
    }

    $DB->insert_records('block_lord_dictionary', $params);
}