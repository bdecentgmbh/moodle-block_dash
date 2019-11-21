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

namespace block_dash\data_source;

use block_dash\block_builder;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;

class placeholder_data_source extends abstract_data_source
{
    /**
     * Get human readable name of data source.
     *
     * @return string
     */
    public function get_name()
    {
        return '';
    }

    /**
     * @return string
     */
    public function get_query_template()
    {
        return 'SELECT %%SELECT%% FROM {user} u';
    }

    /**
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public function get_available_field_definitions()
    {
        return block_builder::get_field_definitions([
            'u_id',
            'u_firstname'
        ]);
    }

    /**
     * @return filter_collection_interface
     */
    public function build_filter_collection()
    {
        return new filter_collection(get_class($this), $this->get_context());
    }
}
