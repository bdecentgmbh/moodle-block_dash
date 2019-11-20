<?php

namespace block_dash\template;

use block_dash\block_builder;
use block_dash\data_grid\field\field_definition;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\current_user_condition;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\filter\participants_condition;
use block_dash\data_grid\filter\user_field_filter;
use block_dash\data_grid\filter\user_profile_field_filter;

class user_stat_template extends abstract_template
{
    /**
     * Get human readable name of template.
     *
     * @return string
     */
    public function get_name()
    {
        return get_string('userstat', 'block_dash');
    }

    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_stat';
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
        $filter_collection = new filter_collection(get_class($this), $this->get_context());
        $filter_collection->add_filter(new current_user_condition('u_id', 'u.id'));
        return $filter_collection;
    }

    /**
     * Add form fields to the block edit form. IMPORTANT: Prefix field names with config_ otherwise the values will
     * not be saved.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform)
    {
        $mform->addElement('text', 'config_preferences[stat_field_label]', get_string('label'));
        $mform->setType('config_preferences[stat_field_label]', PARAM_TEXT);
        $mform->setDefault('config_preferences[stat_field_label]', get_string('email'));

        $options = [];
        foreach ($this->get_available_field_definitions() as $field_definition) {
            $options[$field_definition->get_name()] = $field_definition->get_title();
        }

        $mform->addElement('select', 'config_preferences[stat_field_definition]', get_string('display'), $options);
        $mform->setType('config_preferences[stat_field_definition]', PARAM_TEXT);
        $mform->setDefault('config_preferences[stat_field_definition]', 'u_firstname');
    }

    /**
     * Modify objects before data is retrieved.
     */
    public function before_data()
    {
        if (!$statfielddefinition = $this->get_preferences('stat_field_definition')) {
            $statfielddefinition = 'u_firstname';
        }

        foreach ($this->get_data_grid()->get_field_definitions() as $field_definition) {
            $field_definition->set_visibility(field_definition::VISIBILITY_HIDDEN);
        }

        if ($this->get_data_grid()->has_field_definition($statfielddefinition)) {
            $this->get_data_grid()->get_field_definition($statfielddefinition)
                ->set_visibility(field_definition::VISIBILITY_VISIBLE);
        }
    }
}