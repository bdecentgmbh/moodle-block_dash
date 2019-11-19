<?php

namespace block_dash\template;

use block_dash\block_builder;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\filter\participants_condition;
use block_dash\data_grid\filter\user_field_filter;
use block_dash\data_grid\filter\user_profile_field_filter;

class users_template extends abstract_template
{
    /**
     * Get human readable name of template.
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

        $sql .= ' WHERE 1 %%FILTERS%%';

        return $sql;
    }

    /**
     * @return field_definition_interface[]
     */
    public function get_available_field_definitions()
    {
        $fieldnames = [
            'u_id',
            'u_firstname',
            'u_lastname',
            'u_email',
            'u_username',
            'u_idnumber',
            'u_city',
            'u_country',
            'u_lastlogin',
            'u_department',
            'u_institution',
            'u_address',
            'u_alternatename',
            'u_firstaccess',
            'u_description',
            'u_picture',
            'g_id',
            'g_name'
        ];

        foreach (profile_get_custom_fields() as $field) {
            $fieldnames[] = 'u_pf_' . strtolower($field->shortname);
        }

        return block_builder::get_field_definitions($fieldnames);
    }

    /**
     * @return filter_collection_interface
     */
    public function build_filter_collection()
    {
        global $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");

        $filter_collection = new filter_collection(get_class($this), $this->get_context());

        $filter_collection->add_filter(new user_field_filter('u_department', 'u.department', 'department'));
        $filter_collection->add_filter(new user_field_filter('u_institution', 'u.institution', 'institution'));
        $filter_collection->add_filter(new participants_condition('c_id', 'c.id'));

        foreach (profile_get_custom_fields() as $field) {
            $alias = 'u_pf_' . strtolower($field->shortname);
            $filter_collection->add_filter(new user_profile_field_filter($alias, $alias . '.data', $field->id));
        }

        return $filter_collection;
    }
}