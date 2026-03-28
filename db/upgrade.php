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
 * Define external service functions.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade block_dash.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_block_dash_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2020070202) {
        // Data source classes have moved to `local` namespace. Update all instances of Dash that use a class name as
        // the data source idnumber.
        foreach ($DB->get_records('block_instances', ['blockname' => 'dash']) as $record) {
            $instance = block_instance('dash', $record);
            if (isset($instance->config->data_source_idnumber)) {
                $instance->config->data_source_idnumber = str_replace(
                    'block_dash\\',
                    'block_dash\\local\\',
                    $instance->config->data_source_idnumber
                );
                $instance->instance_config_save($instance->config);
            }
        }

        upgrade_plugin_savepoint(true, 2020070202, 'block', 'dash');
    }

    if ($oldversion < 2026030500) {
        // Remap layout identifiers from local_dash namespace to block_dash namespace.
        // Layouts have been moved to block_dash for standalone availability.
        $layoutmapping = [
            'local_dash\\layout\\cards_layout'         => 'block_dash\\local\\layout\\cards_layout',
            'local_dash\\layout\\cards_slider_layout'  => 'block_dash\\local\\layout\\cards_slider_layout',
            'local_dash\\layout\\cards_masonry_layout' => 'block_dash\\local\\layout\\cards_masonry_layout',
            'local_dash\\layout\\accordion_layout'     => 'block_dash\\local\\layout\\accordion_layout',
            'local_dash\\layout\\accordion_layout2'    => 'block_dash\\local\\layout\\accordion_layout2',
            'local_dash\\layout\\one_stat_layout'      => 'block_dash\\local\\layout\\one_stat_layout',
            'local_dash\\layout\\two_stat_layout'      => 'block_dash\\local\\layout\\two_stat_layout',
            'local_dash\\layout\\timeline_layout'      => 'block_dash\\local\\layout\\timeline_layout',
        ];

        $oldidentifiers = array_keys($layoutmapping);

        foreach ($DB->get_records('block_instances', ['blockname' => 'dash']) as $record) {
            $config = unserialize(base64_decode($record->configdata));
            if (empty($config) || !isset($config->preferences['layout'])) {
                continue;
            }
            $currentlayout = $config->preferences['layout'];
            if (in_array($currentlayout, $oldidentifiers)) {
                $config->preferences['layout'] = $layoutmapping[$currentlayout];
                $record->configdata = base64_encode(serialize($config));
                $DB->update_record('block_instances', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2026030500, 'block', 'dash');
    }

    return true;
}
