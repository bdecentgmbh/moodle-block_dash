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

use block_dash\data_grid\field\field_definition;
use block_dash\data_grid\field\user_profile_link_field_definition;
use block_dash\data_grid\field\user_profile_url_field_definition;
use block_dash\data_grid\field\date_field_definition;
use block_dash\data_source\form\preferences_form;
use block_dash\layout\grid_layout;
use block_dash\data_source\users_data_source;
use block_dash\data_grid\field\user_image_url_field_definition;
use block_dash\data_grid\field\user_image_field_definition;

defined('MOODLE_INTERNAL') || die();

function block_dash_register_field_definitions() {
    global $CFG;

    require_once("$CFG->dirroot/user/profile/lib.php");

    $definitions = [
        new field_definition(['user'], 'u_id', 'u.id', get_string('user') . ' ID'),
        new field_definition(['user'], 'u_firstname', 'u.firstname', get_string('firstname')),
        new field_definition(['user'], 'u_lastname', 'u.lastname', get_string('lastname')),
        new field_definition(['user'], 'u_email', 'u.email', get_string('email')),
        new field_definition(['user'], 'u_username', 'u.username', get_string('username')),
        new field_definition(['user'], 'u_idnumber', 'u.idnumber', get_string('idnumber')),
        new field_definition(['user'], 'u_city', 'u.city', get_string('city')),
        new field_definition(['user'], 'u_country', 'u.country', get_string('country')),
        new date_field_definition(['user'], 'u_lastlogin', 'u.lastlogin', get_string('lastlogin')),
        new field_definition(['user'], 'u_department', 'u.department', get_string('department')),
        new field_definition(['user'], 'u_institution', 'u.institution', get_string('institution')),
        new field_definition(['user'], 'u_address', 'u.address', get_string('address')),
        new field_definition(['user'], 'u_alternatename', 'u.alternatename', get_string('alternatename')),
        new date_field_definition(['user'], 'u_firstaccess', 'u.firstaccess', get_string('firstaccess')),
        new field_definition(['user'], 'u_description', 'u.description', get_string('description')),
        new user_image_url_field_definition(['user'], 'u_picture_url', 'u.id', get_string('pictureofuser') .' URL'),
        new user_image_field_definition(['user'], 'u_picture', 'u.id', get_string('pictureofuser')),
        new user_profile_url_field_definition(['user'], 'u_profile_url', 'u.id', 'User profile URL'),
        new user_profile_link_field_definition(['user'], 'u_profile_link', 'u.id', 'User profile link')
    ];

    $i = 0;
    foreach (profile_get_custom_fields() as $custom_field) {
        $definitions[] = new field_definition(
            ['user'],
            'u_pf_' . strtolower($custom_field->shortname),
            "(SELECT profile$i.data FROM {user_info_data} profile$i 
            WHERE profile$i.userid = u.id AND profile$i.fieldid = $custom_field->id)",
            $custom_field->name);

        $i++;
    }

    $definitions = array_merge($definitions, [
        new field_definition(['group'], 'g_id', 'g.id', get_string('group') . ' ID'),
        new field_definition(['group'], 'g_name', 'g.name', get_string('group'))
    ]);

    return $definitions;
}

function block_dash_register_data_sources() {
    return [
        [
            'name' => get_string('users'),
            'class' => users_data_source::class
        ]
    ];
}

function block_dash_register_layouts() {
    return [
        [
            'name' => get_string('layoutgrid', 'block_dash'),
            'class' => grid_layout::class
        ],
        [
            'name' => get_string('layoutonestat', 'block_dash'),
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

    $form = new preferences_form(null, ['block' => $block], 'post', '', ['class' => 'info-dash-preferences-form']);

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