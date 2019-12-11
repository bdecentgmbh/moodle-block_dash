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
 * Version details
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\field;

use block_dash\data_grid\field\attribute\field_attribute_interface;

/**
 * Responsible for building field definitions and retrieving them as needed.
 *
 * @package block_dash\data_grid\field
 */
class field_definition_factory
{
    /**
     * @var field_definition_interface[]
     */
    private static $all_field_definitions = null;

    /**
     * @return field_definition_interface[]
     */
    public static function get_all_field_definitions()
    {
        if (is_null(self::$all_field_definitions)) {
            self::$all_field_definitions = [];
            if ($pluginsfunction = get_plugins_with_function('register_field_definitions')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $field_definition) {
                            $_field_definition = new sql_field_definition(
                                $field_definition['select'],
                                $field_definition['name'],
                                $field_definition['title'],
                                isset($field_definition['visibility']) ? $field_definition['visibility'] :
                                    field_definition_interface::VISIBILITY_VISIBLE,
                                isset($field_definition['options']) ? $field_definition['options'] : []);

                            if (isset($field_definition['tables'])) {
                                $_field_definition->set_option('tables', $field_definition['tables']);
                            }

                            // Support adding attributes from configuration array.
                            if (isset($field_definition['attributes'])) {
                                foreach ($field_definition['attributes'] as $attribute) {
                                    /** @var field_attribute_interface $_attribute */
                                    $_attribute = new $attribute['type']();
                                    if (isset($attribute['options'])) {
                                        $_attribute->set_options($attribute['options']);
                                    }
                                    $_field_definition->add_attribute($_attribute);
                                }
                            }

                            self::$all_field_definitions[] = $_field_definition;
                        }
                    }
                }
            }
        }

        return self::$all_field_definitions;
    }

    /**
     * Get field definitions by names. Maintain order.
     *
     * @param string[] $names Field definition names to retrieve.
     * @return field_definition_interface[]
     */
    public static function get_field_definitions(array $names)
    {
        $field_definitions = [];
        $all = self::get_all_field_definitions();

        foreach ($all as $field_definition) {
            if (in_array($field_definition->get_name(), $names)) {
                $field_definitions[array_search($field_definition->get_name(), $names)] = $field_definition;
            }
        }

        ksort($field_definitions);

        return $field_definitions;
    }

    /**
     * @param string $name Field definition name to retrieve.
     * @return field_definition_interface
     */
    public static function get_field_definition($name)
    {
        foreach (self::get_all_field_definitions() as $field_definition) {
            if ($field_definition->get_name() == $name) {
                return $field_definition;
            }
        }

        return null;
    }
}