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
 * Common functions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\data_source\form\preferences_form;
use block_dash\local\layout\grid_layout;
use block_dash\local\layout\accordion_layout;
use block_dash\local\layout\one_stat_layout;
use block_dash\local\data_source\users_data_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Register field definitions.
 *
 * @return array
 */
function block_dash_register_field_definitions() {
    global $CFG;

    if (PHPUNIT_TEST) {
        return require("$CFG->dirroot/blocks/dash/field_definitions_phpunit.php");
    }

    return require("$CFG->dirroot/blocks/dash/field_definitions.php");
}

/**
 * Register data sources.
 *
 * @return array
 * @throws coding_exception
 */
function block_dash_register_data_sources() {
    return [
        [
            'name' => get_string('users'),
            'identifier' => users_data_source::class
        ]
    ];
}

/**
 * Register layouts.
 *
 * @return array
 * @throws coding_exception
 */
function block_dash_register_layouts() {
    return [
        [
            'name' => get_string('layoutgrid', 'block_dash'),
            'identifier' => grid_layout::class
        ]
    ];
}

/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function block_dash_output_fragment_block_preferences_form($args) {
    global $DB, $OUTPUT;

    $args = (object) $args;
    $context = $args->context;

    $blockinstance = $DB->get_record('block_instances', ['id' => $context->instanceid]);
    $block = block_instance($blockinstance->blockname, $blockinstance);

    if (!$args->tab) {
        $args->tab = preferences_form::TAB_GENERAL;
    }

    $form = new preferences_form(null, ['block' => $block, 'tab' => $args->tab], 'post', '', [
        'class' => 'dash-preferences-form',
        'data-double-submit-protection' => 'off'
    ]);

    require_capability('block/dash:addinstance', $context);

    if (isset($block->config->preferences)) {
        $data = block_dash_flatten_array($block->config->preferences, 'config_preferences');
        $form->set_data($data);
    }

    ob_start();
    $form->display();
    $formhtml = ob_get_contents();
    ob_end_clean();

    $tabs = [];
    foreach (preferences_form::TABS as $tab) {
        $tabs[] = [
            'label' => get_string($tab, 'block_dash'),
            'active' => $tab == $args->tab,
            'tabid' => $tab
        ];
    }

    return $OUTPUT->render_from_template('block_dash/preferences_form', [
        'formhtml' => $formhtml,
        'tabs' => $tabs
    ]);
}

/**
 * Flatten array to form field names.
 *
 * @param array $array
 * @param string $prefix
 * @return array
 */
function block_dash_flatten_array($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        if (is_integer($key)) {
            // Don't flatten arrays with numeric indexes. Otherwise it won't be set on the Moodle form.
            $result[$prefix] = $array;
        } else if (is_array($value)) {
            $result = $result + block_dash_flatten_array($value, $prefix . '[' . $key . ']');
        } else {
            $result[$prefix . '[' . $key . ']'] = $value;
        }
    }
    return $result;
}

/**
 * Check if system is Totara.
 *
 * @return bool
 */
function block_dash_is_totara() {
    global $CFG;
    return file_exists("$CFG->dirroot/totara");
}

/**
 * Check if pro plugin is installed.
 *
 * @return bool
 */
function block_dash_has_pro() {
    return array_key_exists('dash', core_component::get_plugin_list('local'));
}