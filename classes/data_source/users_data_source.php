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

namespace block_dash\data_source;

use block_dash\block_builder;
use block_dash\data_grid\field\field_definition_factory;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\date_filter;
use block_dash\data_grid\filter\filter;
use block_dash\data_grid\filter\group_filter;
use block_dash\data_grid\filter\logged_in_user_condition;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\filter\my_groups_condition;
use block_dash\data_grid\filter\participants_condition;
use block_dash\data_grid\filter\user_field_filter;
use block_dash\data_grid\filter\user_profile_field_filter;
use block_dash\data_grid\filter\current_course_condition;

class users_data_source extends abstract_data_source
{
    /**
     * Get human readable name of data source.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('users');
    }

    /**
     * @return string
     */
    public function get_query_template()
    {
        global $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");
        $sql = 'SELECT DISTINCT %%SELECT%% FROM {user} u 
                LEFT JOIN {user_enrolments} AS ue ON ue.userid = u.id
                LEFT JOIN {enrol} AS e ON e.id = ue.enrolid
                LEFT JOIN {course} AS c ON c.id = e.courseid
                LEFT JOIN {groups_members} AS gm ON gm.userid = u.id
                LEFT JOIN {groups} AS g ON g.id = gm.groupid ';

        foreach (profile_get_custom_fields() as $field) {
            $alias = 'u_pf_' . strtolower($field->shortname);
            $sql .= "LEFT JOIN {user_info_data} AS $alias ON $alias.userid = u.id AND $alias.fieldid = $field->id ";
        }

        $sql .= ' %%WHERE%% %%GROUPBY%% %%ORDERBY%%';

        return $sql;
    }

    public function get_groupby()
    {
        return 'u.id, g.id';
    }

    public function build_available_field_definitions()
    {
        return field_definition_factory::get_field_definitions_by_tables(['u', 'g']);
    }

    /**
     * @return filter_collection_interface
     */
    public function build_filter_collection()
    {
        global $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");

        $filter_collection = new filter_collection(get_class($this), $this->get_context());

        $filter_collection->add_filter(new group_filter('group', 'g.id'));

        $filter_collection->add_filter(new user_field_filter('u_department', 'u.department', 'department',
            get_string('department')));
        $filter_collection->add_filter(new user_field_filter('u_institution', 'u.institution', 'institution',
            get_string('institution')));

        $filter = new date_filter('u_lastlogin', 'u.lastlogin', date_filter::DATE_FUNCTION_FLOOR,
            get_string('lastlogin'));
        $filter->set_operation(filter::OPERATION_GREATER_THAN_EQUAL);
        $filter_collection->add_filter($filter);

        $filter = new date_filter('u_firstaccess', 'u.firstaccess', date_filter::DATE_FUNCTION_FLOOR,
            get_string('firstaccess'));
        $filter->set_operation(filter::OPERATION_GREATER_THAN_EQUAL);
        $filter_collection->add_filter($filter);

        $filter_collection->add_filter(new logged_in_user_condition('current_user', 'u.id'));
        $filter_collection->add_filter(new participants_condition('participants', 'u.id'));
        $filter_collection->add_filter(new my_groups_condition('my_groups', 'g.id'));
        $filter_collection->add_filter(new current_course_condition('current_course', 'c.id'));
        $filter_collection->add_filter(new current_course_condition('current_course_groups', 'g.courseid',
            get_string('currentcoursegroups', 'block_dash')));

        foreach (profile_get_custom_fields() as $field) {
            $alias = 'u_pf_' . strtolower($field->shortname);
            $filter = new user_profile_field_filter($alias, $alias . '.data', $field->id, $field->name);
            $filter->set_label($field->name);
            $filter_collection->add_filter($filter);
        }

        return $filter_collection;
    }
}