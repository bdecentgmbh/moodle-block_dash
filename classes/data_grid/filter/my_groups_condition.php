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
 * Limits data to logged in user's groups.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Limits data to logged in user's groups.
 *
 * @package block_dash
 */
class my_groups_condition extends condition {

    /**
     * Condition values.
     *
     * @var array
     */
    private $values;

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_values() {
        if (is_null($this->values)) {
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
                $this->values[] = $group->id;
            }

            if (!$this->values) {
                $this->values = [0];
            }
        }

        return $this->values;
    }

    /**
     * Get filter label.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_label() {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('mygroups', 'group');
    }
}