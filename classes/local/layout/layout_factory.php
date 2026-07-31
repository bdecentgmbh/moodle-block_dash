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
 * Responsible for creating layouts on request.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_source\data_source_interface;
/**
 * Responsible for creating layouts on request.
 *
 * @package block_dash
 */
class layout_factory implements layout_factory_interface {
    /**
     * @var array
     */
    private static $layoutregistry;

    /**
     * Build and get layout registry.
     *
     * @return array
     */
    protected static function get_layout_registry() {
        if (is_null(self::$layoutregistry)) {
            self::$layoutregistry = [];
            if ($pluginsfunction = get_plugins_with_function('register_layouts')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $layoutinfo) {
                            self::$layoutregistry[$layoutinfo['identifier']] = $layoutinfo;
                        }
                    }
                }
            }
        }

        return self::$layoutregistry;
    }

    /**
     * Check if layout identifier exists.
     *
     * @param string $identifier
     * @return bool
     */
    public static function exists($identifier) {
        return isset(self::get_layout_registry()[$identifier]);
    }

    /**
     * Get layout info based on layout identifier.
     *
     * @param string $identifier
     * @return array|null
     */
    public static function get_layout_info($identifier) {
        if (self::exists($identifier)) {
            return self::get_layout_registry()[$identifier];
        }

        return null;
    }

    /**
     * Map old local_dash layout identifiers to new block_dash identifiers.
     * @var array
     */
    private static $layoutmigrationmap = [
        'local_dash\layout\cards_layout'         => 'block_dash\local\layout\cards_layout',
        'local_dash\layout\cards_slider_layout'  => 'block_dash\local\layout\cards_slider_layout',
        'local_dash\layout\cards_masonry_layout' => 'block_dash\local\layout\cards_masonry_layout',
        'local_dash\layout\accordion_layout'     => 'block_dash\local\layout\accordion_layout',
        'local_dash\layout\accordion_layout2'    => 'block_dash\local\layout\accordion_layout2',
        'local_dash\layout\one_stat_layout'      => 'block_dash\local\layout\one_stat_layout',
        'local_dash\layout\two_stat_layout'      => 'block_dash\local\layout\two_stat_layout',
        'local_dash\layout\timeline_layout'      => 'block_dash\local\layout\timeline_layout',
    ];

    /**
     * Get layout object with datasource.
     *
     * @param string $identifier
     * @param data_source_interface $datasource
     * @return layout_interface|null
     */
    public static function build_layout($identifier, data_source_interface $datasource) {
        // Runtime fallback: remap old local_dash identifiers to block_dash.
        if (!self::exists($identifier) && isset(self::$layoutmigrationmap[$identifier])) {
            $identifier = self::$layoutmigrationmap[$identifier];
        }

        if (!self::exists($identifier)) {
            return null;
        }

        $layoutinfo = self::get_layout_info($identifier);

        if (isset($layoutinfo['factory']) && $layoutinfo['factory'] != self::class) {
            return $layoutinfo['factory']::build_layout($identifier, $datasource);
        }

        if (class_exists($identifier)) {
            return new $identifier($datasource);
        }

        return null;
    }

    /**
     * Get options array for select form fields.
     *
     * @return array
     */
    public static function get_layout_form_options() {
        $options = [];

        foreach (self::get_layout_registry() as $identifier => $layoutinfo) {
            if (!isset($layoutinfo['type']) || in_array($layoutinfo['type'], ['block', 'both'])) {
                $options[$identifier] = $layoutinfo['name'];
            }
        }

        return $options;
    }
    /**
     * Get options array for details area custom content select form field.
     *
     * @return array
     */
    public static function get_details_area_form_options() {
        $options = [];

        foreach (self::get_layout_registry() as $identifier => $layoutinfo) {
            if (isset($layoutinfo['type']) && in_array($layoutinfo['type'], ['detailsarea', 'both'])) {
                $options[$identifier] = $layoutinfo['name'];
            }
        }

        return $options;
    }
}
