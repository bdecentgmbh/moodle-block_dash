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
 * Global settings definition for block dash.
 * @package   block_dash
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once("$CFG->dirroot/blocks/dash/lib.php");

    // Default high scores.
    $settings->add(new admin_setting_configselect(
        'block_dash/bootstrap_version',
        get_string('bootstrapversion', 'block_dash'),
        get_string('bootstrapversion_desc', 'block_dash'),
        block_dash_is_totara() ? 3 : 4,
        [
            3 => 'Bootstrap 3.x',
            4 => 'Bootstrap 4.x'
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dash/disableall',
        get_string('disableall', 'block_dash'),
        get_string('disableall_help', 'block_dash'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dash/exportdata',
        get_string('defaultexportdata', 'block_dash'),
        get_string('defaultexportdata_help', 'block_dash'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestinterests',
        get_string('suggestinterests', 'block_dash'),
        get_string('suggestinterests_desc', 'block_dash'), 0, PARAM_INT)
    );

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestcohort',
        get_string('suggestcohort', 'block_dash'),
        get_string('suggestcohort_desc', 'block_dash'), 0, PARAM_INT)
    );

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestgroups',
        get_string('suggestgroups', 'block_dash'),
        get_string('suggestgroups_desc', 'block_dash'), 0, PARAM_INT)
    );

    $users = block_dash_get_suggest_users();
    $settings->add(new admin_setting_configmultiselect(
        'block_dash/suggestusers',
        get_string('suggestusers', 'block_dash'),
        get_string('suggestusers_desc', 'block_dash'), [], $users)
    );

    $PAGE->requires->js_amd_inline("
        require(['core/form-autocomplete'], function(module) {
            module.enhance('#id_s_block_dash_suggestusers');
        });
    ");
}
