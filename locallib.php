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
 * List of moodleforms used for various uses.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->dirroot.'/lib/formslib.php');
require($CFG->dirroot . '/lib/tablelib.php');

use block_dash\local\data_source\data_source_interface;

/**
 * Moodleform to create group with course selector option.
 */
class create_group extends moodleform {
    /**
     * Get the definition of the moodle form.
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $attrs = $mform->getAttributes();
        $attrs['id'] = 'block-dash';
        $mform->setAttributes($attrs);

        $mform->addElement('text', 'name', get_string('groupname', 'group'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $courses = enrol_get_my_courses();
        $courselist = [];
        foreach ($courses as $course) {
            $courseelement = (class_exists('\core_course_list_element'))
            ? new \core_course_list_element($course) : new \course_in_list($course);
            $courselist[$course->id] = $courseelement->get_formatted_fullname();
        }
        $mform->addElement('autocomplete', 'courseid', get_string('course'), $courselist);
        $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons();
    }
}

/**
 * Table class for download the dash block as csv or any other format.
 */
class block_dash_download_table extends table_sql {
    /**
     * Dash block datasource.
     * @var data_source_interface|null
     */
    public $datasource = null;

    /**
     * Raw sql from datasource query.
     * @var string
     */
    public $sql;

    /**
     * Raw sql params from datasource query.
     * @var array
     */
    public $params;

    /**
     * Set the datasource for the table.
     * @param data_source_interface $datasource
     */
    public function set_datasource(data_source_interface $datasource) {
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
