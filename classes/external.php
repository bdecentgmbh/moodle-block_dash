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

use block_dash\local\block_builder;
use block_dash\local\data_source\form\preferences_form;
use block_dash\output\renderer;
use external_api;

/**
 * External API class.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    #region get_block_content

    /**
     * Returns description of get_database_schema_structure() parameters.
     *
     * @return \external_function_parameters
     */
    public static function get_block_content_parameters() {
        return new \external_function_parameters([
            'block_instance_id' => new \external_value(PARAM_INT),
            'filter_form_data' => new \external_value(PARAM_RAW),
            'page' => new \external_value(PARAM_INT, 'Paginator page.', VALUE_DEFAULT, 0),
            'sort_field' => new \external_value(PARAM_TEXT, 'Field to sort by', VALUE_DEFAULT, null),
            'sort_direction' => new \external_value(PARAM_TEXT, 'Sort direction of field', VALUE_DEFAULT, null)
        ]);
    }

    /**
     * Get block content.
     *
     * @param int $block_instance_id
     * @param string $filter_form_data
     * @param int $page
     * @param string $sort_field
     * @return array
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \restricted_context_exception
     */
    public static function get_block_content($block_instance_id, $filter_form_data, $page, $sort_field, $sort_direction) {
        global $PAGE, $DB;

        $params = self::validate_parameters(self::get_block_content_parameters(), [
            'block_instance_id' => $block_instance_id,
            'page' => $page,
            'filter_form_data' => $filter_form_data,
            'sort_field' => $sort_field,
            'sort_direction' => $sort_direction
        ]);

        $public = false;
        $blockinstance = $DB->get_record('block_instances', ['id' => $params['block_instance_id']]);
        $block = block_instance($blockinstance->blockname, $blockinstance);
        if (strpos($block->instance->pagetypepattern, 'local-dash-dashboard') !== false) {
            if ($dashboard = \local_dash\model\dashboard::get_record(
                    ['shortname' => $block->instance->defaultregion])) {
                if ($dashboard->get('permission') == \local_dash\model\dashboard::PERMISSION_PUBLIC) {
                    $public = true;
                }
            }
        }

        if (!$public) {
            self::validate_context($block->context);
        } else {
            $PAGE->set_context($block->context);
        }

        /** @var renderer $renderer */
        $renderer = $PAGE->get_renderer('block_dash');

        if ($block) {
            if ($params['sort_field']) {
                $block->set_sort($params['sort_field'], $params['sort_direction']);
            }

            $bb = block_builder::create($block);
            foreach (json_decode($params['filter_form_data'], true) as $filter) {
                $bb->get_configuration()
                    ->get_data_source()
                    ->get_filter_collection()
                    ->apply_filter($filter['name'], $filter['value']);
            }

            $bb->get_configuration()->get_data_source()->get_paginator()->set_current_page($params['page']);

            return ['html' => $renderer->render_data_source($bb->get_configuration()->get_data_source())];
        }

        return ['html' => 'Error'];
    }

    /**
     * Returns description of get_block_content() result value.
     *
     * @return \external_description
     */
    public static function get_block_content_returns() {
        return new \external_single_structure([
            'html' => new \external_value(PARAM_RAW)
        ]);
    }

    #endregion

    #region submit_preferences_form

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return \external_function_parameters
     */
    public static function submit_preferences_form_parameters() {
        return new \external_function_parameters([
            'contextid' => new \external_value(PARAM_INT, 'The context id for the block'),
            'jsonformdata' => new \external_value(PARAM_RAW, 'The form data encoded as a json array')
        ]);
    }

    /**
     * Submit the preferences form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return array
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \required_capability_exception
     * @throws \moodle_exception
     */
    public static function submit_preferences_form($contextid, $jsonformdata) {
        global $DB;

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

        $blockinstance = $DB->get_record('block_instances', ['id' => $context->instanceid]);
        $block = block_instance($blockinstance->blockname, $blockinstance);

        if (!empty($block->config)) {
            $config = clone($block->config);
        } else {
            $config = new \stdClass;
        }

        if (!isset($config->preferences)) {
            $config->preferences = [];
        }

        $config->preferences = self::recursive_config_merge($config->preferences, $data['config_preferences']);
        $block->instance_config_save($config);

        return [
            'validationerrors' => false
        ];
    }

    /**
     * Recursively merge in new config.
     *
     * @param $existingconfig
     * @param $newconfig
     * @return mixed
     */
    private static function recursive_config_merge($existingconfig, $newconfig) {
        // If existing config is a scalar value than always overwrite. No point in looping new config.
        // This allows preferences that were a scalar to be assigned as arrays by new preferences.
        if (is_scalar($existingconfig)) {
            return $newconfig;
        }

        // If array contains only scalars, overwrite with new config. No more looping required for this level.
        if (is_array($existingconfig) && !self::is_array_multidimensional($existingconfig)) {
            return $newconfig;
        }

        // Recursively overwrite values.
        foreach ($newconfig as $key => $value) {
            if (is_scalar($value)) {
                $existingconfig[$key] = $value;
            } else if (is_array($value)) {
                $existingconfig[$key] = self::recursive_config_merge($existingconfig[$key], $newconfig[$key]);
            }
        }

        return $existingconfig;
    }

    /**
     * Check if array is multidimensional. True if it contains an array, false meaning all values are scalar.
     *
     * @param array $array
     * @return bool
     */
    private static function is_array_multidimensional(array $array): bool {
        foreach ($array as $element) {
            if (is_array($element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns description of method result value.
     *
     * @return \external_description
     * @since Moodle 3.0
     */
    public static function submit_preferences_form_returns() {
        return new \external_single_structure([
            'validationerrors' => new \external_value(PARAM_BOOL, 'Were there validation errors', VALUE_REQUIRED),
        ]);
    }

    #endregion
}
