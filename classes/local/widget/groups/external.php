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
 * Group widget external functions contains the add memebrs, get non group members list.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\groups;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/blocks/dash/lib.php');

/**
 * Group widget external function definitions.
 *
 * Each function is called from a My groups widget in a Dash block. It checks the widget's capability in that block,
 * and then what the current user may do with the group or course concerned, see {@see access}.
 */
class external extends external_api {
    /**
     * Validates the block a request comes from and the capability the widget needs there.
     *
     * @param int $contextid Context of the Dash block that shows the widget.
     * @param string $capability
     * @return \context_block
     */
    protected static function validate_widget_context(int $contextid, string $capability): \context_block {
        $context = \context::instance_by_id($contextid);
        block_dash_get_block_instance_by_context($context);
        self::validate_context($context);
        require_capability($capability, $context);

        return $context;
    }

    /**
     * Get the users details if not assigned to the group.
     *
     * @return external_function_parameters
     */
    public static function get_non_members_parameters() {

        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context of the Dash block that shows the widget'),
            'query' => new external_value(
                PARAM_RAW,
                'Query string (full or partial user full name or other details)'
            ),
            'groupid' => new external_value(PARAM_INT, 'group id'),
        ]);
    }

    /**
     * Returns result type for get_relevant_users function.
     *
     * @return \core_external\external_description Result type
     */
    public static function get_non_members_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User id'),
                    'fullname' => new external_value(PARAM_RAW, 'Full name as text'),
            ])
        );
    }

    /**
     * Searches the users of the group's course who are not members of the group yet.
     *
     * @param int $contextid Context of the Dash block that shows the widget.
     * @param string $query Query text
     * @param int $groupid
     * @return array Defined return structure
     */
    public static function get_non_members($contextid, $query, $groupid) {
        [
            'contextid' => $contextid,
            'query' => $query,
            'groupid' => $groupid,
        ] = self::validate_parameters(self::get_non_members_parameters(), [
            'contextid' => $contextid,
            'query' => $query,
            'groupid' => $groupid,
        ]);

        self::validate_widget_context($contextid, 'block/dash:mygroups_addusers');
        $group = access::require_can_add_members($groupid);

        $potentialmembersselector = new \group_non_members_selector('addselect', [
            'groupid' => $group->id, 'courseid' => $group->courseid,
        ]);
        $list = [];
        foreach ($potentialmembersselector->find_users($query) as $users) {
            foreach ($users as $user) {
                $list[] = ['id' => $user->id, 'fullname' => fullname($user)];
            }
        }

        return $list;
    }

    /**
     * Add members.
     *
     * @return external_function_parameters
     */
    public static function add_members_parameters() {

        return new external_function_parameters(
            [
                'contextid' => new external_value(PARAM_INT, 'Context of the Dash block that shows the widget'),
                'formdata' => new external_value(PARAM_RAW, 'The data from the user notes'),
            ]
        );
    }
    /**
     * Retuns the redirect course url and created pulse id for save method.
     *
     * @return external_value
     */
    public static function add_members_returns() {
        return new external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Add members to the selected group.
     *
     * Only users enrolled in the group's course can be added.
     *
     * @param int $contextid Context of the Dash block that shows the widget.
     * @param string $formdata
     * @return bool
     */
    public static function add_members($contextid, $formdata) {
        [
            'contextid' => $contextid,
            'formdata' => $formdata,
        ] = self::validate_parameters(self::add_members_parameters(), [
            'contextid' => $contextid,
            'formdata' => $formdata,
        ]);

        self::validate_widget_context($contextid, 'block/dash:mygroups_addusers');
        parse_str($formdata, $data);
        $group = access::require_can_add_members(clean_param($data['groupid'] ?? 0, PARAM_INT));
        $userids = array_filter(clean_param_array((array) ($data['users'] ?? []), PARAM_INT));
        if (!$userids) {
            return false;
        }

        $coursecontext = \context_course::instance($group->courseid);
        foreach ($userids as $userid) {
            if (!is_enrolled($coursecontext, $userid)) {
                throw new \invalid_parameter_exception('User ' . $userid . ' is not enrolled in the course of the group.');
            }
        }
        foreach ($userids as $userid) {
            groups_add_member($group, $userid);
        }

        return true;
    }

    /**
     * Prameters definition that helps to leave from the own group.
     *
     * @return external_function_parameters
     */
    public static function leave_group_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context of the Dash block that shows the widget'),
            'groupid' => new external_value(PARAM_INT, 'group id'),
        ]);
    }

    /**
     * Return data of leave group service.
     *
     * @return external_value
     */
    public static function leave_group_returns() {
        return new external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Remove the current user from one of their groups, unless the membership is managed by a plugin.
     *
     * @param int $contextid Context of the Dash block that shows the widget.
     * @param int $groupid
     * @return bool
     */
    public static function leave_group($contextid, $groupid) {
        global $USER;

        ['contextid' => $contextid, 'groupid' => $groupid] = self::validate_parameters(self::leave_group_parameters(), [
            'contextid' => $contextid,
            'groupid' => $groupid,
        ]);

        self::validate_widget_context($contextid, 'block/dash:mygroups_leavegroup');
        $group = groups_get_group($groupid, '*', MUST_EXIST);
        if (!groups_is_member($group->id, $USER->id) || !groups_remove_member_allowed($group, $USER->id)) {
            return false;
        }

        return groups_remove_member($group, $USER->id);
    }

    /**
     * Prameters definition that helps to create groups in the selected course.
     *
     * @return external_function_parameters
     */
    public static function create_group_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context of the Dash block that shows the widget'),
            'formdata' => new external_value(PARAM_RAW, 'The data from the user notes'),
        ]);
    }

    /**
     * Returns the status of group creation in course.
     *
     * @return external_value
     */
    public static function create_group_returns() {
        return new external_value(PARAM_BOOL, 'Result of members added.');
    }

    /**
     * Create a group in one of the current user's courses, with the user as its first member.
     *
     * The group gets a name and nothing else; everything else stays for the course's teachers to set.
     *
     * @param int $contextid Context of the Dash block that shows the widget.
     * @param string $formdata
     * @return bool
     */
    public static function create_group($contextid, $formdata) {
        global $USER;

        [
            'contextid' => $contextid,
            'formdata' => $formdata,
        ] = self::validate_parameters(self::create_group_parameters(), [
            'contextid' => $contextid,
            'formdata' => $formdata,
        ]);

        self::validate_widget_context($contextid, 'block/dash:mygroups_creategroup');
        parse_str($formdata, $data);
        $courseid = clean_param($data['courseid'] ?? 0, PARAM_INT);
        $name = \core_text::substr(trim(clean_param($data['name'] ?? '', PARAM_TEXT)), 0, 254);
        if (!$courseid || $name === '') {
            return false;
        }
        access::require_can_create_group($courseid);
        if (groups_get_group_by_name($courseid, $name)) {
            throw new \moodle_exception('groupnameexists', 'group', '', $name);
        }

        $groupid = groups_create_group((object) ['courseid' => $courseid, 'name' => $name]);
        groups_add_member($groupid, $USER->id);

        return true;
    }
}
