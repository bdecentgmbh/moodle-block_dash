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

/**
 * Responsible for creating data sources on request.
 *
 * @package block_dash\data_source
 */
class data_source_factory
{
    /**
     * @var array
     */
    private static $data_source_registry;

    /**
     * @return array
     * @throws \dml_exception
     */
    protected static function get_data_source_registry()
    {
        global $DB;

        if (is_null(self::$data_source_registry)) {
            self::$data_source_registry = [];
            if ($pluginsfunction = get_plugins_with_function('register_data_sources')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $datasourceinfo) {
                            $datasourceinfo['is_custom'] = false;
                            self::$data_source_registry[$datasourceinfo['class']] = $datasourceinfo;
                        }
                    }
                }
            }

//            foreach ($DB->get_records('dash_data_source') as $record) {
//                $record = (array)$record;
//                $record['is_custom'] = true;
//
//                self::$data_source_registry[$record['idnumber']] = $record;
//            }
        }

        return self::$data_source_registry;
    }

    /**
     * Check if data source identifier references a custom data source. If it does, the identifier is the idnumber to the
     * database record.
     *
     * @param string $identifier
     * @return bool
     * @throws \dml_exception
     */
    public static function is_custom($identifier)
    {
        return array_key_exists($identifier, self::get_data_source_registry())
            && self::get_data_source_registry()[$identifier]['is_custom'];
    }

    /**
     * Check if data source identifier exists.
     *
     * @param string $identifier
     * @return bool
     * @throws \dml_exception
     */
    public static function exists($identifier)
    {
        return isset(self::get_data_source_registry()[$identifier]);
    }

    /**
     * @param $identifier
     * @return array|null
     * @throws \dml_exception
     */
    public static function get_data_source_info($identifier)
    {
        if (self::exists($identifier)) {
            return self::get_data_source_registry()[$identifier];
        }

        return null;
    }

    /**
     * @param string $identifier
     * @param \context $context
     * @return data_source_interface
     * @throws \dml_exception
     */
    public static function get_data_source($identifier, \context $context)
    {
        if (!self::exists($identifier)) {
            return null;
        }

        $datasourceinfo = self::get_data_source_info($identifier);

        if (self::is_custom($identifier)) {
            //return new custom_data_source($datasourceinfo, $context);
        } else {
            if (class_exists($identifier)) {
                return new $identifier($context);
            }
        }

        return null;
    }

    /**
     * Get options array for select form fields.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_data_source_form_options()
    {
        $options = [];

        foreach (self::get_data_source_registry() as $identifier => $datasourceinfo) {
            $options[$identifier] = $datasourceinfo['name'];
        }

        return $options;
    }
}