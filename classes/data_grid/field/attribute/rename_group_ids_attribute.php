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
 * Transform data by renaming delimited group IDs to group names. Remove groups not in context.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\field\attribute;

use block_dash\data_grid\filter\group_filter;
use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Transform data by renaming delimited group IDs to group names. Remove groups not in context.
 *
 * @package block_dash
 */
class rename_group_ids_attribute extends rename_ids_attribute {

    private $groupsincontext;

    /**
     * Check if group exists in context.
     *
     * @param $id
     * @return bool
     */
    public function check_id($id) {
        return array_key_exists($id, $this->get_groups_in_context());
    }

    /**
     * Query groups in this context once.
     *
     * @return array
     * @throws coding_exception
     */
    private function get_groups_in_context() {
        global $USER;

        if (!isset($this->groupincontext)) {
            $this->groupsincontext = [];

            if ($context = $this->get_field_definition()->get_data_grid()->get_data_source()->get_context()) {
                foreach (group_filter::get_user_groups($USER->id, $context) as $group) {
                    $this->groupsincontext[$group->id] = $group;
                }
            }
        }

        return $this->groupsincontext;
    }
}