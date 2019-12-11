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

namespace block_dash\data_grid\data\strategy;

use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_interface;

class grouped_strategy implements data_strategy_interface
{
    /**
     * @var field_definition_interface
     */
    private $groupby_field_definition;

    /**
     * @var field_definition_interface
     */
    private $group_label_field_definition;

    /**
     * @param field_definition_interface $groupby_field_definition
     * @param field_definition_interface $group_label_field_definition
     */
    public function __construct(field_definition_interface $groupby_field_definition,
                                field_definition_interface $group_label_field_definition)
    {
        $this->groupby_field_definition = $groupby_field_definition;
        $this->group_label_field_definition = $group_label_field_definition;
    }

    /**
     * @param \stdClass[] $records
     * @param data_grid_interface $data_grid
     * @return data_collection_interface
     */
    public function convert_records_to_data_collection($records, data_grid_interface $data_grid)
    {
        $grid_data = new data_collection();

        $sections = [];
        foreach ($records as $record) {
            $row = new data_collection();

            $label = $record->{$this->group_label_field_definition->get_name()};
            if (!$groupby = $record->{$this->groupby_field_definition->get_name()}) {
                continue;
            }

            foreach ($data_grid->get_field_definitions() as $field_definition) {
                $name = $field_definition->get_name();

                if ($field_definition->get_visibility() == field_definition_interface::VISIBILITY_HIDDEN) {
                    unset($record->$name);
                    continue;
                }

                $record->$name = $field_definition->transform_data($record->$name, $record);
            }

            $row->add_data_associative($record);
            if (!isset($sections[$groupby])) {
                $sections[$groupby] = new data_collection();
                $sections[$groupby]->add_data_associative([
                    'groupby' => $groupby,
                    'label' => $label
                ]);
            }
            $sections[$groupby]->add_child_collection('rows', $row);
        }

        foreach ($sections as $section) {
            $grid_data->add_child_collection('sections', $section);
        }

        return $grid_data;
    }
}
