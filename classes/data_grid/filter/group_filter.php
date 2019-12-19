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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_dash\data_grid\filter;

/**
 * Filter by groups the user has access to.
 *
 * @package block_dash\data_grid\filter
 */
class group_filter extends select_filter
{
    /**
     * @var array
     */
    private $values;

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init()
    {
        global $USER, $CFG;

        require_once("$CFG->dirroot/lib/enrollib.php");
        require_once("$CFG->dirroot/lib/grouplib.php");

        $this->values = [];

        $courses = enrol_get_my_courses();

        $groups = [];
        foreach ($courses as $course) {
            if (has_capability('moodle/site:accessallgroups', \context_course::instance($course->id))) {
                $groups = array_merge($groups, groups_get_all_groups($course->id));
            } else {
                $groups = array_merge($groups, groups_get_all_groups($course->id, $USER->id));
            }
        }

        foreach ($groups as $group) {
            $this->add_option($group->id, $group->name);
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function get_label()
    {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('group', 'group');
    }
}