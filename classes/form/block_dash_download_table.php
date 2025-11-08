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
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

/**
 * Table class for download the dash block as csv or any other format.
 */
class block_dash_download_table extends \table_sql {
    /**
     * Dash block datasource.
     * @var \block_dash\local\data_source\data_source_interface|null
     */
    public $datasource = null;

    /**
     * Raw sql from datasource query.
     * @var string|null
     */
    public $sql;

    /**
     * Raw sql params from datasource query.
     * @var array|null
     */
    public $params;

    /**
     * Set the datasource for the table.
     * @param \block_dash\local\data_source\data_source_interface $datasource
     */
    public function set_datasource(\block_dash\local\data_source\data_source_interface $datasource) {
        $this->datasource = $datasource;
    }

    /**
     * Set the sql and param for the table.
     *
     * @param string $sql
     * @param array $params
     */
    public function set_data($sql, $params) {
        global $DB;
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * Query the database and load the data.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        $this->rawdata = $DB->get_records_sql($this->sql, $this->params);
    }

    /**
     * Formats a single row of data for output based on dash data collection stratergy.
     *
     * @param mixed $record
     * @return array
     *
     */
    public function format_row($record) {

        if (is_array($record)) {
            $record = (object) $record;
        }

        $formattedrow = [];

        foreach ($this->datasource->get_sorted_fields() as $fielddefinition) {
            $name = $fielddefinition->get_alias();

            if (!property_exists($record, $name)) {
                continue;
            }

            $formattedcolumn = $fielddefinition->transform_data($record->$name, $record);
            $formattedrow[$name] = strip_tags($formattedcolumn);
        }

        return $formattedrow;
    }
}
