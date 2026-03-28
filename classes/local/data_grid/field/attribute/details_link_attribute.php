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
 * Renders a stretched "Details" link that opens the details area via JavaScript.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

/**
 * Renders a stretched link element with the CSS hook class used by JS to open the details area.
 *
 * @package block_dash
 */
class details_link_attribute extends abstract_field_attribute {

    /**
     * Transform the raw data into a stretched "Details" link element.
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     */
    public function transform_data($data, \stdClass $record) {
        if ($data) {
            $label = get_string('details_link', 'block_dash');
            $value = 'r' . substr(md5(json_encode((array) $record)), 0, 8);
            $blockinstanceid = (int) $this->get_option('blockinstanceid');
            $detailid = self::build_detail_id($record, $blockinstanceid);
            return \html_writer::link(
                '#' . $detailid,
                $label,
                [
                    'class' => 'stretched-link dash-details-open-link',
                    'data-action' => 'open-details-modal',
                    'data-detail-id' => $detailid,
                ]
            );
        }
        return $data;
    }

    /**
     * Build a deterministic, page-unique detail ID from the block instance and record.
     *
     * The ID is used both as the URL fragment and as a data attribute so that
     * the JS can match location.hash to the correct trigger element.
     *
     * Format: dash-detail-b{blockid}-{record identifiers}
     *
     * @param \stdClass $record Full database record row.
     * @param int $blockinstanceid The block_instances.id (0 if unknown).
     * @return string A stable identifier string (without leading #).
     */
    public static function build_detail_id(\stdClass $record, int $blockinstanceid = 0): string {
        $parts = ['b' . $blockinstanceid];
        $hasrecordid = false;
        /* if (!empty($record->c_id)) {
            $parts[] = 'c' . (int) $record->c_id;
            $hasrecordid = true;
        }
        if (!empty($record->u_id)) {
            $parts[] = 'u' . (int) $record->u_id;
            $hasrecordid = true;
        }
        if (!empty($record->cc_id)) {
            $parts[] = 'cc' . (int) $record->cc_id;
            $hasrecordid = true;
        }
        if (!empty($record->g_id)) {
            $parts[] = 'g' . (int) $record->g_id;
            $hasrecordid = true;
        } */
        if (!$hasrecordid) {
            // Fallback: short hash from the record's scalar values.
            $parts[] = 'r' . substr(md5(json_encode((array) $record)), 0, 8);
        }
        return 'dash-detail-' . implode('-', $parts);
    }
}
