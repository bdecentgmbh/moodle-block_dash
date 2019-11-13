<?php

namespace block_dash\template;

use block_dash\block_builder;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\choice_filter;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;

class users_template extends abstract_template
{
    /**
     * Get human readable name of template.
     *
     * @return string
     */
    public function get_name()
    {
        return 'Users';
    }

    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_grid';
    }

    /**
     * @return string
     */
    public function get_query_template()
    {
        return 'SELECT %%SELECT%% FROM {user} u
                WHERE 1 %%FILTERS%%';
    }

    /**
     * @return field_definition_interface[]
     */
    public function get_available_field_definitions()
    {
        return block_builder::get_field_definitions([
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
            'u_picture'
        ]);
    }

    /**
     * @return filter_collection_interface
     */
    public function get_filter_collection()
    {
        global $USER;

        $filter_collection = new filter_collection('123');
        $filter_collection->add_filter(new choice_filter($USER, [
            'admin' => 'Admin User',
            'Guest' => 'guest'
        ], 'u_username', get_string('user')));
        $filter_collection->add_column_mapping('u_username', 'u.username');

        return $filter_collection;
    }
}