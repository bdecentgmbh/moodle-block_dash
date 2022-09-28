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
 * Transform data to user's profile image URL.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;
/**
 * Transform data to user's profile image URL.
 *
 * @package block_dash
 */
class widget_attribute extends abstract_field_attribute {

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date fsormat
     *
     * @param mixed $data
     * @param \stdClass $record Entire row
     * @return mixed
     * @throws \moodle_exception
     */
    public function transform_data($data, \stdClass $record) {
        global $PAGE, $DB;
        $widget = $this->get_option('widget');
        $method = $this->get_option('method');
        if ($widget && $method) {
            if (method_exists($widget, $method)) {
                $data = $widget->$method($record);
            }
        }

        return $data;
    }
}
