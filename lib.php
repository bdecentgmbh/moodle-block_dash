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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\data_source\form\preferences_form;
use block_dash\layout\grid_layout;
use block_dash\layout\accordion_layout;
use block_dash\layout\one_stat_layout;
use block_dash\data_source\users_data_source;

defined('MOODLE_INTERNAL') || die();

function block_dash_register_field_definitions() {
    global $CFG;

    if (PHPUNIT_TEST) {
        return require("$CFG->dirroot/blocks/dash/field_definitions_phpunit.php");
    }

    return require("$CFG->dirroot/blocks/dash/field_definitions.php");
}

function block_dash_register_data_sources() {
    return [
        [
            'name' => get_string('users'),
            'identifier' => users_data_source::class
        ]
    ];
}

function block_dash_register_layouts() {
    return [
        [
            'name' => get_string('layoutgrid', 'block_dash'),
            'class' => grid_layout::class
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
    global $CFG;

    $args = (object) $args;
    $context = $args->context;
    $o = '';

    $block = block_instance_by_id($context->instanceid);

    $form = new preferences_form(null, ['block' => $block], 'post', '', ['class' => 'dash-preferences-form']);

    require_capability('block/dash:addinstance', $context);

    if (isset($block->config->preferences)) {
        $data = block_dash_flatten_array($block->config->preferences, 'config_preferences');
        $form->set_data($data);
    }

    ob_start();
    $form->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

function block_dash_flatten_array($array, $prefix = '')
{
    $result = array();
    foreach($array as $key=>$value) {
        if(is_array($value)) {
            $result = $result + block_dash_flatten_array($value, $prefix . '[' . $key . ']');
        }
        else {
            $result[$prefix . '[' . $key . ']'] = $value;
        }
    }
    return $result;
}