<?php


namespace block_dash\template;

use block_dash\data_grid\configurable_data_grid;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\layout\grid_layout;
use block_dash\layout\layout_interface;
use block_dash\layout\one_stat_layout;

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

    /**
     * @var array
     */
    private $preferences = [];

    /**
     * @var layout_interface
     */
    private $layout;

    /**
     * @param \context $context
     */
    public function __construct(\context $context)
    {
        $this->context = $context;
    }

    /**
     * Get data grid. Build if necessary.
     *
     * @return data_grid_interface
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public final function get_data_grid()
    {
        if (is_null($this->data_grid)) {
            $this->data_grid = new configurable_data_grid($this->get_context());
            $this->data_grid->set_query_template($this->get_query_template());
            $this->data_grid->set_field_definitions($this->get_available_field_definitions());
            $this->data_grid->set_supports_pagination($this->get_layout()->supports_pagination());
            $this->data_grid->init();
        }

        return $this->data_grid;
    }

    /**
     * Get filter collection for data grid. Build if necessary.
     *
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

        if ($this->preferences && isset($this->preferences['filters'])) {
            foreach ($this->preferences['filters'] as $filtername => $preference) {
                if (isset($preference['enabled']) && !$preference['enabled']) {
                    if ($filter = $this->get_filter_collection()->get_filter($filtername)) {
                        $this->get_filter_collection()->remove_filter($filter);
                    }
                }
            }
        }

        $this->get_layout()->before_data();
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
        $this->get_layout()->after_data();
    }

    /**
     * @return layout_interface
     */
    public function get_layout()
    {
        if (is_null($this->layout)) {
            if ($layout = $this->get_preferences('layout')) {
                $this->layout = new $layout($this);
            } else {
                $this->layout = new grid_layout($this);
            }
        }

        return $this->layout;
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
        return $this->get_layout()->export_for_template($output);
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
        $mform->addElement('select', 'config_preferences[layout]', get_string('layout', 'block_dash'), [
            grid_layout::class => get_string('layoutgrid', 'block_dash'),
            one_stat_layout::class => get_string('layoutonestat', 'block_dash')
        ]);
        $mform->setType('config_preferences[layout]', PARAM_TEXT);

        if ($layout = $this->get_layout()) {
            $layout->build_preferences_form($form, $mform);
        }
    }

    #region Preferences

    /**
     * Get a specific preference.
     *
     * @param string $name
     * @return mixed|array
     */
    public final function get_preferences($name)
    {
        if ($this->preferences && isset($this->preferences[$name])) {
            return $this->preferences[$name];
        }

        return [];
    }

    /**
     * Get all preferences associated with the template.
     *
     * @return array
     */
    public final function get_all_preferences()
    {
        return $this->preferences;
    }

    /**
     * Set preferences on this template.
     *
     * @param array $preferences
     */
    public final function set_preferences(array $preferences)
    {
        $this->preferences = $preferences;
    }

    #endregion
}
