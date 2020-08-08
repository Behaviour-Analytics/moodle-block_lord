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
 * The global block settings.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Show the block settings header.
    $settings->add(new admin_setting_heading(
        'headerconfig',
        get_string('adminheader', 'block_lord'),
        ''
    ));

    // The checkbox for showing the block.
    $settings->add(new admin_setting_configcheckbox(
        'block_lord/showblock',
        get_string('showblocklabel', 'block_lord'),
        get_string('showblockdesc', 'block_lord'),
        '1'
    ));

    // The checkbox for starting/stopping the relation discovery process.
    $settings->add(new admin_setting_configcheckbox(
        'block_lord/start',
        get_string('startlabel', 'block_lord'),
        get_string('startdesc', 'block_lord'),
        '0'
    ));
}
