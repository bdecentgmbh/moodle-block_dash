<?php
// This file is part of The Bootstrap Moodle theme
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
 * Used for unit testing a generic data grid.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid;

use block_dash\local\data_grid\data\data_collection;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\field\field_definition_interface;
use block_dash\local\data_grid\filter\filter_collection_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * Used for unit testing a generic data grid.
 *
 * @package block_dash
 */
class testing_data_grid extends data_grid {

    /**
     * Execute and return data collection.
     *
     * @return data_collection_interface
     * @since 2.2
     */
    public function get_data() {
        $collection = new data_collection();

        foreach ($this->get_testing_records() as $record) {
            $row = new data_collection();
            foreach ($this->get_field_definitions() as $fielddefinition) {
                $name = $fielddefinition->get_name();

                if ($fielddefinition->get_visibility() == field_definition_interface::VISIBILITY_HIDDEN) {
                    unset($record->$name);
                    continue;
                }

                $record->$name = $fielddefinition->transform_data($record->$name, $record);
            }

            $row->add_data_associative($record);
            $collection->add_child_collection('users', $row);
        }

        return $collection;
    }

    /**
     * Do not query database, just returned dummy data.
     *
     * @return array
     */
    protected function get_testing_records() {
        $records = [];

        $record = new \stdClass();
        $record->u_id = 1;
        $record->u_firstname = 'Guest';
        $record->u_lastname = 'Guest';
        $record->u_firstaccess = 1575912875;
        $record->u_picture = 1;
        $records[] = $record;

        $record = new \stdClass();
        $record->u_id = 2;
        $record->u_firstname = 'Admin';
        $record->u_lastname = 'User';
        $record->u_firstaccess = 1575912875;
        $record->u_picture = 2;
        $records[] = $record;

        return $records;
    }

    /**
     * Get total number of records for pagination.
     *
     * @return int
     */
    public function get_count() {
        return 100;
    }
}
