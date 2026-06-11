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
 * Renders a "Details" button that opens the details area via JavaScript.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

/**
 * Renders a button element with the CSS hook class used by JS to open the details area.
 *
 * Carries the same data-detail-id as the link variant so that the JS can
 * match location.hash for auto-open and so external scripts can read the ID.
 *
 * @package block_dash
 */
class details_button_attribute extends abstract_field_attribute {
    /**
     * Transform the raw data into a "Details" button element.
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     */
    public function transform_data($data, \stdClass $record) {
        if ($data) {
            $label = get_string('details', 'block_dash');
            $blockinstanceid = (int) $this->get_option('blockinstanceid');
            $detailid = details_link_attribute::build_detail_id($record, $blockinstanceid);
            return \html_writer::tag(
                'button',
                $label,
                [
                    'type' => 'button',
                    'class' => 'btn btn-secondary dash-details-open-btn',
                    'data-action' => 'open-details-modal',
                    'data-detail-id' => $detailid,
                    'data-status' => $this->get_option('showdetailsarea') ? 'enabled' : 'disabled',
                ]
            );
        }
        return $data;
    }
}
