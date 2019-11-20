<?php


namespace block_dash\template;

use block_dash\data_grid\configurable_data_grid;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\paginator;
use block_dash\output\renderer;

abstract class abstract_template implements template_interface, \templatable
{
    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_grid_interface
     */
    private $data_grid;

    /**
     * @var data_collection_interface
     */
    private $data;

    /**
     * @var filter_collection_interface
     */
    private $filter_collection;

    private $template_name = 'block_dash/layout_missing';

    /**
     * @var array
     */
    private $preferences = [];

    /**
     * @param \context $context
     */
    public function __construct(\context $context)
    {
        $this->context = $context;
    }

    public final function get_data_grid()
    {
        if (is_null($this->data_grid)) {
            $this->data_grid = new configurable_data_grid($this->get_context());
            $this->data_grid->set_query_template($this->get_query_template());
            $this->data_grid->set_field_definitions($this->get_available_field_definitions());
            $this->data_grid->init();
        }

        return $this->data_grid;
    }

    /**
     * @return filter_collection_interface
     */
    public final function get_filter_collection()
    {
        if (is_null($this->filter_collection)) {
            $this->filter_collection = $this->build_filter_collection();
        }

        return $this->filter_collection;
    }

    /**
     * Modify objects before data is retrieved.
     */
    public function before_data()
    {
        if ($this->preferences && isset($this->preferences['available_fields'])) {
            foreach ($this->preferences['available_fields'] as $fieldname => $preferences) {
                if (isset($preferences['visible'])) {
                    if ($fielddefinition = $this->get_data_grid()->get_field_definition($fieldname)) {
                        $fielddefinition->set_visibility($preferences['visible']);
                    }
                }
            }
        }
    }

    /**
     * @return data_collection_interface
     * @throws \moodle_exception
     */
    public final function get_data()
    {
        if (is_null($this->data)) {
            $this->before_data();

            $data_grid = $this->get_data_grid();
            $data_grid->set_filter_collection($this->get_filter_collection());
            $this->data = $data_grid->get_data();

            $this->after_data();
        }

        return $this->data;
    }

    /**
     * Modify objects after data is retrieved.
     */
    public function after_data()
    {

    }

    /**
     * @return \context
     */
    public function get_context()
    {
        return $this->context;
    }

    /**
     * @param \renderer_base $output
     * @return array|\renderer_base|\stdClass|string
     * @throws \coding_exception
     */
    public final function export_for_template(\renderer_base $output)
    {
        global $OUTPUT;

        $templatedata = [
            'error' => ''
        ];

        try {
            $data = $this->get_data();
        } catch (\Exception $e) {
            $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
            if (is_siteadmin()) {
                $error .= \html_writer::tag('p', $e->getMessage());
            }

            $templatedata['error'] .= $OUTPUT->notification($error, 'error');
        }

        $formhtml = $this->get_filter_collection()->create_form_elements();

        if (isset($data)) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'data' => $data,
                'paginator' => $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_data_grid()->get_paginator()
                    ->export_for_template($OUTPUT)),
                'preferences' => $this->get_all_preferences()
            ]);
        }

        return $templatedata;
    }

    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_grid';
    }

    /**
     * @param string $template_name
     */
    public function set_mustache_template_name($template_name)
    {
        $this->template_name = $template_name;
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
        $group = [];
        foreach ($this->get_available_field_definitions() as $available_field_definition) {
            $fieldname = 'config_preferences[available_fields][' . $available_field_definition->get_name() . '][visible]';
            $group[] = $mform->createElement('advcheckbox', $fieldname, $available_field_definition->get_title(), null,
                ['group' => 1]);
            $mform->setDefault($fieldname, 1);
            $mform->setType($fieldname, PARAM_BOOL);
        }
        $mform->addGroup($group, null, get_string('enabledfields', 'block_dash'));
        $form->add_checkbox_controller(1);
    }

    /**
     * @param string $name
     * @return array
     */
    public final function get_preferences($name)
    {
        if ($this->preferences && isset($this->preferences[$name])) {
            return $this->preferences[$name];
        }

        return [];
    }

    public final function get_all_preferences()
    {
        return $this->preferences;
    }

    /**
     * @param array $preferences
     */
    public final function set_preferences(array $preferences)
    {
        $this->preferences = $preferences;
    }
}
