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
 * This script is used to save resource node coordinates.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

defined('MOODLE_INTERNAL') || die();

$courseid = required_param('cid', PARAM_INT);
$nodedata = required_param('data', PARAM_RAW);

require_sesskey();

$course = get_course($courseid);

require_login($course);
$context = context_course::instance($courseid);
require_capability('block/lord:view', $context);

$nodes = json_decode($nodedata);

// Build new records.
$data = [];
$nds = [];
$coordsid = $nodes->time;

foreach ($nodes->nodes as $key => $value) {

    $data[] = (object) array(
        'courseid' => $courseid,
        'changed'  => $coordsid,
        'moduleid' => $key,
        'xcoord'   => $value->xcoord,
        'ycoord'   => $value->ycoord,
        'visible'  => 1
    );
}
// Store new node coordinates.
$DB->insert_records('block_lord_coords', $data);

$DB->insert_record('block_lord_scales', (object) array(
    'courseid'  => $courseid,
    'coordsid'  => $coordsid,
    'scale'     => $nodes->scale,
    'iscustom'  => $nodes->iscustom,
    'mindist'   => $nodes->mindist,
    'maxdist'   => $nodes->maxdist,
    'distscale' => $nodes->distscale,
));

// Insert the link weights into the DB.
$data = [];
foreach ($nodes->links as $link) {

    if (!is_numeric($link->source->id) || !is_numeric($link->target->id)) {
        continue;
    }

    $m1 = $link->source->id;
    $m2 = $link->target->id;

    if ($m1 > $m2) {
        $m1 = $link->target->id;
        $m2 = $link->source->id;
    }

    $data[] = (object) array(
        'courseid' => $courseid,
        'coordsid' => $coordsid,
        'module1'  => $m1,
        'module2'  => $m2,
        'weight'   => $link->weight
    );
}
// Store new links.
$DB->insert_records('block_lord_links', $data);

die('Graph configuration saved.');

