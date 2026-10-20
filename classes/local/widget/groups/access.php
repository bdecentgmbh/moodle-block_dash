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

namespace block_dash\local\widget\groups;

/**
 * Permission checks of the My groups widget, shared by its web services and its fragment.
 *
 * The widget's own capabilities decide what the widget offers in a block. These checks decide what the current
 * user may do with a particular group or course, the way core's group pages do.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {
    /**
     * Returns the group if the current user may add members to it: a group they belong to, or any group of a course
     * whose groups they manage.
     *
     * @param int $groupid
     * @return \stdClass The group.
     * @throws \required_capability_exception
     */
    public static function require_can_add_members(int $groupid): \stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $group = groups_get_group($groupid, '*', MUST_EXIST);
        $coursecontext = \context_course::instance($group->courseid);
        if (!groups_is_member($group->id) && !has_capability('moodle/course:managegroups', $coursecontext)) {
            throw new \required_capability_exception($coursecontext, 'moodle/course:managegroups', 'nopermissions', '');
        }

        return $group;
    }

    /**
     * Returns the course context if the current user may create a group in the course: they are enrolled in it, or
     * they manage its groups.
     *
     * @param int $courseid
     * @return \context_course
     * @throws \required_capability_exception
     */
    public static function require_can_create_group(int $courseid): \context_course {
        $coursecontext = \context_course::instance($courseid);
        if (!is_enrolled($coursecontext, null, '', true) && !has_capability('moodle/course:managegroups', $coursecontext)) {
            throw new \required_capability_exception($coursecontext, 'moodle/course:managegroups', 'nopermissions', '');
        }

        return $coursecontext;
    }
}
