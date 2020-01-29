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
 * Responsible for building field definitions and retrieving them as needed.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\data_grid\field;

use block_dash\data_grid\field\attribute\field_attribute_interface;

defined('MOODLE_INTERNAL') || die();

/**
 * Responsible for building field definitions and retrieving them as needed.
 *
 * @package block_dash
 */
class field_definition_factory {

    /**
     * @var field_definition_interface[]
     */
    private static $allfielddefinitions = null;

    /**
     * Returns all reigstered field definitions.
     *
     * @return field_definition_interface[]
     */
    public static function get_all_field_definitions() {
        if (is_null(self::$allfielddefinitions)) {
            self::$allfielddefinitions = [];
            if ($pluginsfunction = get_plugins_with_function('register_field_definitions')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $fielddefinition) {
                            $newfielddefinition = new sql_field_definition(
                                $fielddefinition['select'],
                                $fielddefinition['name'],
                                $fielddefinition['title'],
                                isset($fielddefinition['visibility']) ? $fielddefinition['visibility'] :
                                    field_definition_interface::VISIBILITY_VISIBLE,
                                isset($fielddefinition['options']) ? $fielddefinition['options'] : []);

                            if (isset($fielddefinition['tables'])) {
                                $newfielddefinition->set_option('tables', $fielddefinition['tables']);
                            }

                            // Support adding attributes from configuration array.
                            if (isset($fielddefinition['attributes'])) {
                                foreach ($fielddefinition['attributes'] as $attribute) {
                                    /** @var field_attribute_interface $newattribute */
                                    $newattribute = new $attribute['type']();
                                    if (isset($attribute['options'])) {
                                        $newattribute->set_options($attribute['options']);
                                    }
                                    $newfielddefinition->add_attribute($newattribute);
                                }
                            }

                            self::$allfielddefinitions[] = $newfielddefinition;
                        }
                    }
                }
            }
        }

        return self::$allfielddefinitions;
    }

    /**
     * Get field definitions by names. Maintain order.
     *
     * @param string[] $names Field definition names to retrieve.
     * @return field_definition_interface[]
     */
    public static function get_field_definitions(array $names) {
        $fielddefinitions = [];
        $all = self::get_all_field_definitions();

        foreach ($all as $fielddefinition) {
            if (in_array($fielddefinition->get_name(), $names)) {
                $fielddefinitions[array_search($fielddefinition->get_name(), $names)] = clone $fielddefinition;
            }
        }

        ksort($fielddefinitions);

        return $fielddefinitions;
    }

    /**
     * Get field definition by name.
     *
     * @param string $name Field definition name to retrieve.
     * @return field_definition_interface
     */
    public static function get_field_definition($name) {
        foreach (self::get_all_field_definitions() as $fielddefinition) {
            if ($fielddefinition->get_name() == $name) {
                return clone $fielddefinition;
            }
        }

        return null;
    }

    /**
     * Get field definitions by table alias.
     *
     * @param array $tablealiases
     * @return array
     */
    public static function get_field_definitions_by_tables(array $tablealiases) {
        $fielddefinitions = [];
        $all = self::get_all_field_definitions();

        foreach ($all as $fielddefinition) {
            if ($tables = $fielddefinition->get_option('tables')) {
                if (array_intersect($tablealiases, $tables)) {
                    $fielddefinitions[] = clone $fielddefinition;
                }
            }
        }

        return $fielddefinitions;
    }

    /**
     * Get options for form select field.
     *
     * @param field_definition_interface[] $fielddefinitions
     * @return array
     * @throws \coding_exception
     */
    public static function get_field_definition_options($fielddefinitions) {
        $options = [];
        foreach ($fielddefinitions as $fielddefinition) {

            $tablenames = [];
            if ($tables = $fielddefinition->get_option('tables')) {
                foreach ($tables as $table) {
                    $tablenames[] = get_string('tablealias_' . $table, 'block_dash');
                }
            }

            if ($tablenames) {
                $title = implode(', ', $tablenames);
            } else {
                $title = get_string('general');
            }

            $title = $title . ': ' . $fielddefinition->get_title();

            $options[$fielddefinition->get_name()] = $title;
        }

        return $options;
    }
}