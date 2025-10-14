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
        $records = $DB->get_records('block_instances', ['blockname' => 'dash']);
        
        foreach ($records as $record) {
            if (!empty($record->configdata)) {
                $config = unserialize(base64_decode($record->configdata));
                
                if (isset($config->data_source_idnumber)) {
                    // Update the namespace from 'block_dash\\' to 'block_dash\\local\\'
                    $config->data_source_idnumber = str_replace(
                        'block_dash\\', 
                        'block_dash\\local\\',
                        $config->data_source_idnumber
                    );
                    
                    // Save directly to database
                    $record->configdata = base64_encode(serialize($config));
                    $DB->update_record('block_instances', $record);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2020070202, 'block', 'dash');
    }

    return true;
}
