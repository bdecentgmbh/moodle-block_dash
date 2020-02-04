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
 * Class standard_strategy.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\data\strategy;

use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data\field;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class standard_strategy.
 *
 * @package block_dash
 */
class standard_strategy implements data_strategy_interface {

    /**
     * Convert records.
     *
     * @param \stdClass[] $records
     * @param data_grid_interface $datagrid
     * @return data_collection_interface
     */
    public function convert_records_to_data_collection($records, data_grid_interface $datagrid) {
        $griddata = new data_collection();

        foreach ($records as $fullrecord) {
            $record = clone $fullrecord;
            if (isset($record->unique_id)) {
                unset($record->unique_id);
            }
            $row = new data_collection();
            foreach ($datagrid->get_field_definitions() as $fielddefinition) {
                $name = $fielddefinition->get_name();

                if ($fielddefinition->get_visibility() == field_definition_interface::VISIBILITY_HIDDEN) {
                    continue;
                }

                $row->add_data(new field($name, $fielddefinition->transform_data($record->$name, $fullrecord),
                    $fielddefinition->get_title()));
            }

            $griddata->add_child_collection('rows', $row);
        }

        return $griddata;
    }
}
