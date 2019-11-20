<?php


namespace block_dash\layout;

use block_dash\data_grid\filter\condition;
use block_dash\template\template_interface;

/**
 * Extend this class when creating new layouts.
 *
 * @package block_dash\layout
 */
abstract class abstract_layout implements layout_interface
{
    /**
     * @var template_interface
     */
    private $template;

    public function __construct(template_interface $template)
    {
        $this->template = $template;
    }

    /**
     * @return template_interface
     */
    public function get_template()
    {
        return $this->template;
    }

    /**
     * Modify objects before data is retrieved in the template.
     */
    public function before_data()
    {

    }

    /**
     * Modify objects after data is retrieved in the template.
     */
    public function after_data()
    {

    }

    /**
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform)
    {
        $filter_collection = $this->get_template()->get_filter_collection();

        if ($this->supports_field_visibility()) {
            $group = [];
            foreach ($this->get_template()->get_available_field_definitions() as $available_field_definition) {
                $fieldname = 'config_preferences[available_fields][' . $available_field_definition->get_name() . '][visible]';
                $group[] = $mform->createElement('advcheckbox', $fieldname, $available_field_definition->get_title(), null,
                    ['group' => 1]);
                $mform->setDefault($fieldname, 1);
                $mform->setType($fieldname, PARAM_BOOL);
            }
            $mform->addGroup($group, null, get_string('enabledfields', 'block_dash'));
            $form->add_checkbox_controller(1);
        }

        if ($this->supports_filtering()) {
            $group = [];
            foreach ($filter_collection->get_filters() as $filter) {
                if ($filter instanceof condition) {
                    // Don't include conditions in this group.
                    continue;
                }
                $fieldname = 'config_preferences[filters][' . $filter->get_name() . '][enabled]';
                $group[] = $mform->createElement('advcheckbox', $fieldname, $filter->get_label(), null, ['group' => 2]);
                $mform->setDefault($fieldname, 1);
                $mform->setType($fieldname, PARAM_BOOL);
            }
            $mform->addGroup($group, null, get_string('enabledfilters', 'block_dash'), ['<br>']);
            $form->add_checkbox_controller(2);
        }

        $group = [];
        foreach ($filter_collection->get_filters() as $filter) {
            if (!$filter instanceof condition) {
                // Only include conditions in this group.
                continue;
            }
            $fieldname = 'config_preferences[filters][' . $filter->get_name() . '][enabled]';
            $group[] = $mform->createElement('advcheckbox', $fieldname, $filter->get_label(), null, ['group' => 3]);
            $mform->setDefault($fieldname, 1);
            $mform->setType($fieldname, PARAM_BOOL);
        }
        $mform->addGroup($group, null, get_string('enabledconditions', 'block_dash'), ['<br>']);
        $form->add_checkbox_controller(3);
    }
}