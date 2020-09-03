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
 * Privacy Subsystem implementation for block_lord.
 *
 * @package    block_lord
 * @author     Ted Krahn
 * @copyright  2020 Athabasca University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_lord\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem implementation for block_lord.
 *
 * @author     Ted Krahn
 * @copyright  2020 Athabasca University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns information about how block_lord stores its data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'block_lord_comparisons',
            [
                'courseid' => 'privacy:metadata:block_lord:courseid',
                'module1'  => 'privacy:metadata:block_lord:module1',
                'module2'  => 'privacy:metadata:block_lord:module2',
                'compared' => 'privacy:metadata:block_lord:compared',
                'value'    => 'privacy:metadata:block_lord:value',
                'matrix'   => 'privacy:metadata:block_lord:matrix',
            ],
            'privacy:metadata:block_lord_comparisons'
        );

        $collection->add_database_table(
            'block_lord_modules',
            [
                'courseid' => 'privacy:metadata:block_lord:courseid',
                'module'   => 'privacy:metadata:block_lord:module',
                'name'     => 'privacy:metadata:block_lord:name',
                'intro'    => 'privacy:metadata:block_lord:intro',
            ],
            'privacy:metadata:block_lord_modules'
        );

        $collection->add_database_table(
            'block_lord_max_words',
            [
                'courseid'       => 'privacy:metadata:block_lord:courseid',
                'dodiscovery'    => 'privacy:metadata:block_lord:dodiscovery',
                'maxlength'      => 'privacy:metadata:block_lord:maxlength',
                'maxsentence'    => 'privacy:metadata:block_lord:maxsentence',
                'maxparas'       => 'privacy:metadata:block_lord:maxparas',
                'nameweight'     => 'privacy:metadata:block_lord:nameweight',
                'introweight'    => 'privacy:metadata:block_lord:introweight',
                'sentenceweight' => 'privacy:metadata:block_lord:sentenceweight',
            ],
            'privacy:metadata:block_lord_max_words'
        );

        $collection->add_database_table(
            'block_lord_coords',
            [
                'courseid' => 'privacy:metadata:block_lord:courseid',
                'changed'  => 'privacy:metadata:block_lord:changed',
                'moduleid' => 'privacy:metadata:block_lord:moduleid',
                'xcoord'   => 'privacy:metadata:block_lord:xcoord',
                'ycoord'   => 'privacy:metadata:block_lord:ycoord',
                'visible'  => 'privacy:metadata:block_lord:visible',
            ],
            'privacy:metadata:block_lord_coords'
        );

        $collection->add_database_table(
            'block_lord_scales',
            [
                'courseid'  => 'privacy:metadata:block_lord:courseid',
                'coordsid'  => 'privacy:metadata:block_lord:coordsid',
                'scale'     => 'privacy:metadata:block_lord:scale',
                'iscustom'  => 'privacy:metadata:block_lord:iscustom',
                'mindist'   => 'privacy:metadata:block_lord:mindist',
                'maxdist'   => 'privacy:metadata:block_lord:maxdist',
                'distscale' => 'privacy:metadata:block_lord:distscale',
            ],
            'privacy:metadata:block_lord_scales'
        );

        $collection->add_database_table(
            'block_lord_paragraphs',
            [
                'courseid'  => 'privacy:metadata:block_lord:courseid',
                'module'    => 'privacy:metadata:block_lord:module',
                'paragraph' => 'privacy:metadata:block_lord:paragraph',
                'content'   => 'privacy:metadata:block_lord:content',
            ],
            'privacy:metadata:block_lord_paragraphs'
        );

        $collection->add_database_table(
            'block_lord_dictionary',
            [
                'word'   => 'privacy:metadata:block_lord:word',
                'status' => 'privacy:metadata:block_lord:status',
            ],
            'privacy:metadata:block_lord_dictionary'
        );

        $collection->add_database_table(
            'block_lord_links',
            [
                'courseid' => 'privacy:metadata:block_lord:courseid',
                'coordsid' => 'privacy:metadata:block_lord:coordsid',
                'module1'  => 'privacy:metadata:block_lord:module1',
                'module2'  => 'privacy:metadata:block_lord:module2',
                'weight'   => 'privacy:metadata:block_lord:weight',
            ],
            'privacy:metadata:block_lord_links'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new \core_privacy\local\request\contextlist();

        // The block_lord data is associated at the course context level, so retrieve the user's context id.
        $sql = "SELECT id
                  FROM {context}
                 WHERE contextlevel = :context
                   AND instanceid = :userid
              GROUP BY id";

        $params = [
            'context' => CONTEXT_COURSE,
            'userid'  => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // No userid value in any tables for block_lord, no users.
        return;
    }

    /**
     * Export all user data for the specified user using the Course context level.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // No userid value in any tables for block_lord, no user data to export.
        return;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // No userid value in any tables for block_lord, no user data to delete.
        return;
    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // No userid value in any tables for block_lord, no user data to delete.
        return;
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // No userid value in any tables for block_lord, no user data to delete.
        return;
    }
}
