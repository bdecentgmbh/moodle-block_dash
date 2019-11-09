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

use block_dash\data_grid\field\field_definition;
use block_dash\data_grid\field\user_profile_link_field_definition;

defined('MOODLE_INTERNAL') || die();

function block_dash_register_field_definitions() {
    global $CFG;

    require_once("$CFG->dirroot/user/profile/lib.php");

    $definitions = [
        field_definition::create(['user'], 'u_id', 'u.id', get_string('user') . ' ID'),
        field_definition::create(['user'], 'u_firstname', 'u.firstname', get_string('firstname')),
        field_definition::create(['user'], 'u_lastname', 'u.lastname', get_string('lastname')),
        field_definition::create(['user'], 'u_email', 'u.email', get_string('email')),
        field_definition::create(['user'], 'u_username', 'u.username', get_string('username')),
        field_definition::create(['user'], 'u_idnumber', 'u.idnumber', get_string('idnumber')),
        field_definition::create(['user'], 'u_idnumber', 'u.idnumber', get_string('idnumber')),
        field_definition::create(['user'], 'u_city', 'u.city', get_string('city')),
        field_definition::create(['user'], 'u_country', 'u.country', get_string('country')),
        field_definition::create(['user'], 'u_lastlogin', 'u.lastlogin', get_string('lastlogin')),
        field_definition::create(['user'], 'u_department', 'u.department', get_string('department')),
        field_definition::create(['user'], 'u_institution', 'u.institution', get_string('institution')),
        field_definition::create(['user'], 'u_address', 'u.address', get_string('address')),
        field_definition::create(['user'], 'u_alternatename', 'u.alternatename', get_string('alternatename')),
        field_definition::create(['user'], 'u_firstaccess', 'u.firstaccess', get_string('firstaccess')),
        field_definition::create(['user'], 'u_description', 'u.description', get_string('description')),
        field_definition::create(['user'], 'u_picture', 'u.picture', get_string('pictureofuser')),
        user_profile_link_field_definition::create(['user'], 'u_profile_url', 'u.id', 'User profile URL')
    ];

    $i = 0;
    foreach (profile_get_custom_fields() as $custom_field) {
        $definitions[] = field_definition::create(
            ['user'],
            'u_' . $custom_field->name,
            "(SELECT profile$i.data FROM {user_info_data} profile$i 
            WHERE profile$i.userid = u.id AND profile$i.fieldid = $custom_field->id",
            $custom_field->name);

        $i++;
    }

    return $definitions;
}
