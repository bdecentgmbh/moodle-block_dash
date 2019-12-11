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

use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\field\attribute\identifier_attribute;
use block_dash\data_grid\sql_data_grid;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data_grid_interface;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\condition;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\layout\grid_layout;
use block_dash\layout\layout_factory;
use block_dash\layout\layout_interface;
use block_dash\layout\one_stat_layout;

abstract class abstract_data_source implements data_source_interface, \templatable
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
     * @var field_definition_interface[]
     */
    private $field_definitions;

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
            $this->data_grid = new sql_data_grid($this->get_context());
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
        if ($this->get_layout()->supports_field_visibility()) {
            foreach ($this->get_available_field_definitions() as $available_field_definition) {
                $available_field_definition->set_visibility(field_definition_interface::VISIBILITY_HIDDEN);
            }
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

        if ($this->preferences && isset($this->preferences['filters'])) {
            $enabledfilters = [];
            foreach ($this->preferences['filters'] as $filtername => $preference) {
                if (isset($preference['enabled']) && $preference['enabled']) {
                    $enabledfilters[] = $filtername;
                }
            }
            // No preferences set yet, remove all filters.
            foreach ($this->get_filter_collection()->get_filters() as $filter) {
                if (!in_array($filter->get_name(), $enabledfilters)) {
                    $this->get_filter_collection()->remove_filter($filter);
                }
            }
        } else {
            // No preferences set yet, remove all filters.
            foreach ($this->get_filter_collection()->get_filters() as $filter) {
                $this->get_filter_collection()->remove_filter($filter);
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
            // If the block has no preferences do not query any data.
            if (empty($this->get_all_preferences())) {
                return new data_collection();
            }

            $this->before_data();

            $data_grid = $this->get_data_grid();
            if ($strategy = $this->get_layout()->get_data_strategy()) {
                $data_grid->set_data_strategy($strategy);
            }
            $data_grid->set_filter_collection($this->get_filter_collection());
            $this->data = $data_grid->get_data();

            $this->after_data($this->data);
        }

        return $this->data;
    }

    /**
     * Modify objects after data is retrieved.
     *
     * @param data_collection_interface $data_collection
     */
    public function after_data(data_collection_interface $data_collection)
    {
        $this->get_layout()->after_data($data_collection);
    }

    /**
     * Explicitly set layout.
     *
     * @param layout_interface $layout
     */
    public function set_layout(layout_interface $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return layout_interface
     */
    public function get_layout()
    {
        if (is_null($this->layout)) {
            if ($layout = $this->get_preferences('layout')) {
                if (class_exists($layout)) {
                    $this->layout = new $layout($this);
                }
            }

            // If still null default to grid layout.
            if (is_null($this->layout)) {
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
        $mform->addElement('static', 'data_source_name', get_string('datasource', 'block_dash'), $this->get_name());

        $mform->addElement('select', 'config_preferences[layout]', get_string('layout', 'block_dash'),
            layout_factory::get_layout_form_options());
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
     * Get all preferences associated with the data source.
     *
     * @return array
     */
    public final function get_all_preferences()
    {
        return $this->preferences;
    }

    /**
     * Set preferences on this data source.
     *
     * @param array $preferences
     */
    public final function set_preferences(array $preferences)
    {
        $this->preferences = $preferences;
    }

    #endregion

    /**
     * @return field_definition_interface[]
     */
    public final function get_available_field_definitions()
    {
        if (is_null($this->field_definitions)) {
            $fielddefinitions = $this->build_available_field_definitions();

            if ($this->get_layout()->supports_field_visibility()) {

                $sortedfielddefinitions = [];

                // First add the identifiers, in order, so they always come first in the query.
                foreach ($fielddefinitions as $key => $fielddefinition) {
                    if ($fielddefinition->has_attribute(identifier_attribute::class)) {
                        $sortedfielddefinitions[] = $fielddefinition;
                        unset($fielddefinitions[$key]);
                    }
                }

                if ($availablefields = $this->get_preferences('available_fields')) {
                    foreach ($availablefields as $fieldname => $availablefield) {
                        foreach ($fielddefinitions as $key => $fielddefinition) {
                            if ($fielddefinition->get_name() == $fieldname) {
                                $sortedfielddefinitions[] = $fielddefinition;
                                unset($fielddefinitions[$key]);
                                break;
                            }
                        }
                    }

                    foreach ($fielddefinitions as $fielddefinition) {
                        $sortedfielddefinitions[] = $fielddefinition;
                    }

                    $fielddefinitions = $sortedfielddefinitions;
                }

            }

            $this->field_definitions = $fielddefinitions;
        }

        return $this->field_definitions;
    }
}
