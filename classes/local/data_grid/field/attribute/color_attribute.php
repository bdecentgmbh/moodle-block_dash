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
 * Transforms data into bootstrap badge color class name.
 *
 * @package    block_dash
 * @copyright  2024 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

use html_writer;
use stdClass;

/**
 * Transforms data to badge or css class.
 *
 * @package block_dash
 */
class color_attribute extends abstract_field_attribute {
    /**
     * Transfrom the data to css class by adding the value to the prefix.
     *
     * For badge mode convert this as booststrap badge and add this value + prefix as class name and conttent of the badge.
     *
     * @param string $data
     * @param stdClass $record
     *
     * @return string
     */
    public function transform_data($data, stdClass $record) {

        $result = '';

        if ($prefix = $this->get_option('prefix')) {
            $result .= $prefix;
        }

        $result .= $data;

        if ($this->get_option('badgemode')) {
            $result = " " . html_writer::tag('span', $result, ['class' => 'badge dash-color-badge-mode ' . $result]);
        }

        return $result;
    }
}
