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
 * External API.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use block_dash\template\form\preferences_form;
use block_dash\template\template_factory;
use external_api;

/**
 * External API class.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api
{
    #region get_block_content

    /**
     * Returns description of get_database_schema_structure() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_block_content_parameters()
    {
        return new \external_function_parameters([
            'block_instance_id' => new \external_value(PARAM_INT),
            'filter_form_data' => new \external_value(PARAM_RAW),
            'page' => new \external_value(PARAM_INT, 'Paginator page.', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * @param $block_instance_id
     * @param $filter_form_data
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function get_block_content($block_instance_id, $filter_form_data, $page)
    {
        $params = self::validate_parameters(self::get_block_content_parameters(), [
            'block_instance_id' => $block_instance_id,
            'page' => $page,
            'filter_form_data' => $filter_form_data,
        ]);

        $block = null;
        try {
            $block = block_instance_by_id($params['block_instance_id']);
        } catch (\Exception $e) {

        }

        self::validate_context($block->context);

        if ($block) {
            $bb = block_builder::create($block);
            foreach (json_decode($params['filter_form_data'], true) as $filter) {
                $bb->get_configuration()
                    ->get_template()
                    ->get_filter_collection()
                    ->apply_filter($filter['name'], $filter['value']);
            }

            $bb->get_configuration()->get_template()->get_data_grid()->get_paginator()->set_current_page($params['page']);

            return ['html' => $bb->get_configuration()->get_template()->render()];
        }

        return ['html' => 'Error'];
    }

    /**
     * Returns description of get_block_content() result value.
     *
     * @return \external_description
     */
    public static function get_block_content_returns()
    {
        return new \external_single_structure([
            'html' => new \external_value(PARAM_RAW)
        ]);
    }

    #endregion


    /**
     * Returns description of get_database_schema_structure() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_database_schema_structure_parameters()
    {
        return new \external_function_parameters([]);
    }

    /**
     * Create a new competency framework
     *
     * @return array
     */
    public static function get_database_schema_structure()
    {
        global $DB, $CFG;

        $dbman = $DB->get_manager();

        $schema = new \xmldb_structure('export');
        $schema->setVersion($CFG->version);

        $tables = [];
        foreach ($dbman->get_install_xml_files() as $filename) {
            $xmldb_file = new \xmldb_file($filename);
            if (!$xmldb_file->loadXMLStructure()) {
                continue;
            }
            $structure = $xmldb_file->getStructure();
            foreach ($structure->getTables() as $table) {
                $tablename = '{' . $table->getName() . '}';
                $tables[$tablename] = [];
                foreach ($table->getFields() as $field) {
                    $tables[$tablename][] = $field->getName();
                }
            }
        }

        return ['schema' => json_encode($tables)];
    }

    /**
     * Returns description of get_database_schema_structure() result value.
     *
     * @return \external_description
     */
    public static function get_database_schema_structure_returns()
    {
        return new \external_single_structure([
            'schema' => new \external_value(PARAM_RAW)
        ]);
    }

    /**
     * Returns description of get_database_schema_structure() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_field_edit_row_parameters()
    {
        return new \external_function_parameters([
            'name' => new \external_value(PARAM_TEXT, 'Name of field definition')
        ]);
    }

    /**
     * Create a new competency framework
     *
     * @param string $name
     * @return array
     * @throws \moodle_exception | \coding_exception | \invalid_parameter_exception
     */
    public static function get_field_edit_row($name)
    {
        global $OUTPUT;

        $params = self::validate_parameters(self::get_field_edit_row_parameters(), [
            'name' => $name
        ]);

        self::validate_context(\context_system::instance());

        if ($field_definition = block_builder::get_field_definition($params['name'])) {
            return ['html' => $OUTPUT->render_from_template('block_dash/field_edit_row', $field_definition)];
        }

        throw new \moodle_exception('fieldnotfound', 'block_dash');
    }

    /**
     * Returns description of get_database_schema_structure() result value.
     *
     * @return \external_description
     */
    public static function get_field_edit_row_returns()
    {
        return new \external_single_structure([
            'html' => new \external_value(PARAM_RAW)
        ]);
    }

    #region submit_preferences_form

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return \external_function_parameters
     */
    public static function submit_preferences_form_parameters()
    {
        return new \external_function_parameters([
            'contextid' => new \external_value(PARAM_INT, 'The context id for the block'),
            'jsonformdata' => new \external_value(PARAM_RAW, 'The form data encoded as a json array')
        ]);
    }

    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_preferences_form($contextid, $jsonformdata)
    {
        $params = self::validate_parameters(self::submit_preferences_form_parameters(), [
            'contextid' => $contextid,
            'jsonformdata' => $jsonformdata
        ]);

        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);

        self::validate_context($context);
        require_capability('block/dash:addinstance', $context);

        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();
        parse_str($serialiseddata, $data);

        $block = block_instance_by_id($context->instanceid);

        $form = new preferences_form(null, ['block' => $block], 'post', '', null, true, $data);

        $validationerrors = true;
        if ($validateddata = $form->get_data()) {

            if (!empty($block->config)) {
                $config = clone($block->config);
            } else {
                $config = new stdClass;
            }
            foreach ($validateddata as $configfield => $value) {
                if (strpos($configfield, 'config_') !== 0) {
                    continue;
                }
                $field = substr($configfield, 7);
                $config->$field = $value;
            }
            $block->instance_config_save($config);

            $validationerrors = false;
        } else if ($errors = $form->is_validated()) {
            throw new \moodle_exception('generalerror');
        }


        return [
            'validationerrors' => $validationerrors
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_description
     * @since Moodle 3.0
     */
    public static function submit_preferences_form_returns()
    {
        return new \external_single_structure([
            'validationerrors' => new \external_value(PARAM_BOOL, 'Were there validation errors', VALUE_REQUIRED),
        ]);
    }
}
