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
 * Privacy provider.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * Gallery items are site content, so deleting a user's data keeps the items and only forgets
 * who last changed them.
 *
 * @package    block_dash
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Data stored by the plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_dash_gallery', [
            'usermodified' => 'privacy:metadata:gallery:usermodified',
            'timemodified' => 'privacy:metadata:gallery:timemodified',
        ], 'privacy:metadata:gallery');
        return $collection;
    }

    /**
     * Contexts holding data of a user: the system context, if they changed gallery items.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('block_dash_gallery', ['usermodified' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Users who changed gallery items.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context() instanceof \context_system) {
            $userlist->add_from_sql('usermodified', 'SELECT usermodified FROM {block_dash_gallery}', []);
        }
    }

    /**
     * Export the gallery items a user last changed.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            $records = $DB->get_records(
                'block_dash_gallery',
                ['usermodified' => $contextlist->get_user()->id],
                'shortname',
                'id, shortname, name, timemodified'
            );
            $items = array_map(function ($record) {
                return [
                    'shortname' => $record->shortname,
                    'name' => $record->name,
                    'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                ];
            }, array_values($records));
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'block_dash'), get_string('gallery', 'block_dash')],
                (object) ['items' => $items]
            );
        }
    }

    /**
     * Forget who changed gallery items.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context instanceof \context_system) {
            $DB->set_field('block_dash_gallery', 'usermodified', 0);
        }
    }

    /**
     * Forget that a user changed gallery items.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->set_field('block_dash_gallery', 'usermodified', 0, ['usermodified' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Forget that some users changed gallery items.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        if (!$userlist->get_context() instanceof \context_system || !$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->set_field_select('block_dash_gallery', 'usermodified', 0, "usermodified {$insql}", $params);
    }
}
