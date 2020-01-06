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

namespace block_dash\configuration;

use block_dash\data_source\data_source_factory;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        $parentcontext = \context::instance_by_id($block_instance->instance->parentcontextid);

        $datasource = null;
        if (isset($block_instance->config->data_source_idnumber)) {
            if (!$datasource = data_source_factory::get_data_source($block_instance->config->data_source_idnumber,
                $parentcontext)) {
                throw new \coding_exception('Missing data source.');
            }

            if (isset($block_instance->config->preferences) && is_array($block_instance->config->preferences)) {
                $datasource->set_preferences($block_instance->config->preferences);
            }

            $datasource->set_block_instance($block_instance);
        }

        return new configuration($parentcontext, $datasource);
    }
}
