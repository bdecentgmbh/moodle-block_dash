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
 * Responsible for creating data sources on request.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Responsible for creating data sources on request.
 *
 * @package block_dash
 */
class data_source_factory implements data_source_factory_interface {

    /**
     * Cache registered data sources so they are only retrieved once.
     *
     * @var array
     */
    private static $datasourceregistry;

    /**
     * Register and return data registry.
     *
     * @return array
     */
    protected static function get_data_source_registry() {
        if (is_null(self::$datasourceregistry)) {
            self::$datasourceregistry = [];
            if ($pluginsfunction = get_plugins_with_function('register_data_sources')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $datasourceinfo) {
                            self::$datasourceregistry[$datasourceinfo['identifier']] = $datasourceinfo;
                        }
                    }
                }
            }
        }

        return self::$datasourceregistry;
    }

    /**
     * Check if data source identifier exists.
     *
     * @param string $identifier
     * @return bool
     */
    public static function exists($identifier) {
        return isset(self::get_data_source_registry()[$identifier]);
    }

    /**
     * Get data source info.
     *
     * @param string $identifier
     * @return array|null
     */
    public static function get_data_source_info($identifier) {
        if (self::exists($identifier)) {
            return self::get_data_source_registry()[$identifier];
        }

        return null;
    }

    /**
     * Build data source.
     *
     * @param string $identifier
     * @param \context $context
     * @return data_source_interface
     */
    public static function build_data_source($identifier, \context $context) {
        if (!self::exists($identifier)) {
            return null;
        }

        $datasourceinfo = self::get_data_source_info($identifier);

        if (isset($datasourceinfo['factory']) && $datasourceinfo['factory'] != self::class) {
            return $datasourceinfo['factory']::build_data_source($identifier, $context);
        }

        if (!class_exists($identifier)) {
            return null;
        }

        return new $identifier($context);
    }

    /**
     * Get options array for select form fields.
     *
     * @return array
     */
    public static function get_data_source_form_options() {
        $options = [];

        foreach (self::get_data_source_registry() as $identifier => $datasourceinfo) {
            $options[$identifier] = $datasourceinfo['name'];
        }

        return $options;
    }
}