<?php

namespace block_dash\layout;

use block_dash\data_grid\field\field_definition;

class one_stat_layout extends abstract_layout
{
    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_stat';
    }

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering()
    {
        return false;
    }

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility()
    {
        return false;
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination()
    {
        return false;
    }

    /**
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @return mixed
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform)
    {
        $mform->addElement('text', 'config_preferences[stat_field_label]', get_string('label', 'block_dash'));
        $mform->setType('config_preferences[stat_field_label]', PARAM_TEXT);

        $options = [];
        foreach ($this->get_template()->get_available_field_definitions() as $field_definition) {
            $options[$field_definition->get_name()] = $field_definition->get_title();
        }

        $mform->addElement('select', 'config_preferences[stat_field_definition]',
            get_string('stattodisplay', 'block_dash'), $options);
        $mform->setType('config_preferences[stat_field_definition]', PARAM_TEXT);

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Modify objects before data is retrieved in the template.
     */
    public function before_data()
    {
        parent::before_data();

        if (!$statfielddefinition = $this->get_template()->get_preferences('stat_field_definition')) {
            return;
        }

        foreach ($this->get_template()->get_data_grid()->get_field_definitions() as $field_definition) {
            $field_definition->set_visibility(field_definition::VISIBILITY_HIDDEN);
        }

        if ($this->get_template()->get_data_grid()->has_field_definition($statfielddefinition)) {
            $this->get_template()->get_data_grid()->get_field_definition($statfielddefinition)
                ->set_visibility(field_definition::VISIBILITY_VISIBLE);
        }
    }
}