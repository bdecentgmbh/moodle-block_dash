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
 * Transform data by renaming delimited IDs to fields. Such as course name or group name.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

use dml_exception;

/**
 * Transform data by renaming delimited IDs to fields. Such as course name or group name.
 *
 * @package block_dash
 */
class rename_ids_attribute extends abstract_field_attribute {
    /** @var array stored the fields used in current table.*/
    private static $fieldstore = [];

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data
     * @param \stdClass $record Entire row
     * @return mixed
     * @throws \moodle_exception
     */
    final public function transform_data($data, \stdClass $record) {
        $names = [];
        if ($data) {
            $ids = [];
            foreach (explode($this->get_option('delimiter'), $data) as $id) {
                $id = trim($id);
                if ($id !== '' && $this->check_id($id)) {
                    $ids[] = (int) $id;
                }
            }
            $fields = self::get_fields($this->get_option('table'), $this->get_option('field'), $ids);
            foreach ($ids as $id) {
                if (isset($fields[$id])) {
                    $names[] = format_string($fields[$id], true);
                }
            }
        }
        return implode($this->get_option('delimiter') . ' ', $names);
    }

    /**
     * Override in child class to add additional checks per ID.
     *
     * @param int $id
     * @return bool
     */
    public function check_id($id) {
        return true;
    }

    /**
     * Get the field of the given records of a table, loading only those not loaded before.
     *
     * @param string $table
     * @param string $field
     * @param int[] $ids
     * @return array Record id => field value, for the ids that exist.
     * @throws dml_exception
     */
    protected static function get_fields($table, $field, array $ids) {
        global $DB;

        if (!isset(self::$fieldstore[$table][$field])) {
            self::$fieldstore[$table][$field] = [];
        }
        $missing = array_diff(array_unique($ids), array_keys(self::$fieldstore[$table][$field]));
        if ($missing) {
            [$insql, $params] = $DB->get_in_or_equal($missing);
            $values = $DB->get_records_select_menu($table, "id $insql", $params, '', 'id, ' . $field);
            foreach ($missing as $id) {
                // Remember ids without a record too, so they are not looked up again.
                self::$fieldstore[$table][$field][$id] = $values[$id] ?? null;
            }
        }

        return self::$fieldstore[$table][$field];
    }
}
