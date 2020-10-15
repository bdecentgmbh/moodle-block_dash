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
 * Class abstract_data_source.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\data_grid\data\data_collection;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\data_grid\field\sql_field_definition;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\field\field_definition_interface;
use block_dash\local\data_grid\filter\filter_collection_interface;
use block_dash\local\paginator;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\layout\grid_layout;
use block_dash\local\layout\layout_factory;
use block_dash\local\layout\layout_interface;
use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class abstract_data_source.
 *
 * @package block_dash
 */
abstract class abstract_data_source implements data_source_interface, \templatable {

    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_collection_interface
     */
    private $data;

    /**
     * @var filter_collection_interface
     */
    private $filtercollection;

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
    private $fielddefinitions;

    /**
     * @var field_definition_interface[]
     */
    private $sortedfielddefinitions;

    /**
     * @var \block_base|null
     */
    private $blockinstance = null;

    /**
     * @var builder
     */
    private $query;

    /**
     * @var paginator
     */
    private $paginator;

    /**
     * Constructor.
     *
     * @param \context $context
     */
    public function __construct(\context $context) {
        $this->context = $context;

        $this->paginator = new paginator(function () {
            return $this->get_query()->count();
        });
    }

    /**
     * Get human readable name of data source.
     *
     * @return string
     */
    public function get_name() {
        return self::get_name_from_class(get_class($this));
    }

    /**
     * Get human readable name of data source.
     *
     * @param $fullclassname
     * @return string
     * @throws coding_exception
     */
    public static function get_name_from_class($fullclassname) {
        $component = explode('\\', $fullclassname)[0];
        $class = array_reverse(explode('\\', $fullclassname))[0];

        $stringidentifier = "datasource:$class";
        $stringcomponent = $component;

        $stringmanager = get_string_manager();
        if ($stringmanager->string_exists($stringidentifier, $stringcomponent)) {
            $name = get_string($stringidentifier, $stringcomponent);
        } else if ($stringmanager->string_exists($stringidentifier, 'block_dash')) {
            $name = get_string($stringidentifier, 'block_dash');
        } else {
            $name = '[[' . $stringidentifier . ']]';
        }

        return $name;
    }

    /**
     * @return paginator
     */
    public function get_paginator(): paginator {
        return $this->paginator;
    }

    /**
     * Get fully built query for execution.
     *
     * @return builder
     */
    final public function get_query(): builder {
        if (is_null($this->query)) {
            $this->query = $this->get_query_template();

            if (count($this->get_available_field_definitions()) == 0) {
                throw new \moodle_exception('Cannot build empty query in data source.');
            }

            if ($this->get_filter_collection() && $this->get_filter_collection()->has_filters()) {
                list ($filtersql, $filterparams) = $this->get_filter_collection()->get_sql_and_params();

                $this->query->where_raw($filtersql[0], $filterparams);
            }

            $fields = $this->get_available_field_definitions();

            foreach ($fields as $field) {
                if (is_null($field->get_select())) {
                    continue;
                }

                $this->query->select($field->get_select(), $field->get_name());
            }

            /** @var sql_field_definition $field */
            foreach ($this->get_available_field_definitions() as $field) {
                if ($field->get_sort()) {
                    $this->query->orderby($field->get_select(), strtoupper($field->get_sort_direction()));
                }
            }

            // If there are multiple identifiers in the data source, construct a unique column.
            // This is to prevent warnings when multiple rows have the same value in the first column.
            $identifierselects = [];
            /** @var sql_field_definition $fielddefinition */
            foreach ($this->get_available_field_definitions() as $fielddefinition) {
                if ($fielddefinition->has_attribute(identifier_attribute::class)) {
                    $identifierselects[] = $fielddefinition->get_select();
                }
            }
            global $DB;
            $concat = $DB->sql_concat_join("'-'", $identifierselects);
            if (count($identifierselects) > 1) {
                $this->query->select($concat, 'unique_id');
            }

            if ($this->get_layout()->supports_pagination()) {
                $this->query
                    ->limitfrom($this->get_paginator()->get_limit_from())
                    ->limitnum($this->get_paginator()->get_per_page());
            }

            return $this->query;
        }

        return $this->query;
    }

    /**
     * Get filter collection for data grid. Build if necessary.
     *
     * @return filter_collection_interface
     */
    public final function get_filter_collection() {
        if (is_null($this->filtercollection)) {
            $this->filtercollection = $this->build_filter_collection();
            $this->filtercollection->init();

            if ($this->get_preferences('filters')) {
                foreach ($this->get_preferences('filters') as $filtername => $filterpreferences) {
                    if ($this->filtercollection->has_filter($filtername)) {
                        $this->filtercollection->get_filter($filtername)->set_preferences($filterpreferences);
                    }
                }
            }
        }

        return $this->filtercollection;
    }

    /**
     * Modify objects before data is retrieved.
     */
    public function before_data() {
        if ($this->get_layout()->supports_field_visibility()) {
            foreach ($this->get_available_field_definitions() as $availablefielddefinition) {
                $availablefielddefinition->set_visibility(field_definition_interface::VISIBILITY_HIDDEN);
            }
            if ($this->preferences && isset($this->preferences['available_fields'])) {
                foreach ($this->preferences['available_fields'] as $fieldname => $preferences) {
                    if (isset($preferences['visible'])) {
                        if ($fielddefinition = $this->get_field_definition($fieldname)) {
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
     * Get data collection.
     *
     * @return data_collection_interface
     * @throws \moodle_exception
     */
    public final function get_data() {
        if (is_null($this->data)) {
            // If the block has no preferences do not query any data.
            if (empty($this->get_all_preferences())) {
                return new data_collection();
            }

            $this->before_data();

            if (!$strategy = $this->get_layout()->get_data_strategy()) {
                throw new coding_exception('Not fully configured.');
            }
            $records = $this->get_query()->query();
            $this->data = $strategy->convert_records_to_data_collection($records, $this->get_sorted_field_definitions());

            if ($modifieddata = $this->after_data($this->data)) {
                $this->data = $modifieddata;
            }
        }

        return $this->data;
    }

    /**
     * Modify objects after data is retrieved.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {
        return $this->get_layout()->after_data($datacollection);
    }

    /**
     * Explicitly set layout.
     *
     * @param layout_interface $layout
     */
    public function set_layout(layout_interface $layout) {
        $this->layout = $layout;
    }

    /**
     * Get layout.
     *
     * @return layout_interface
     */
    public function get_layout() {
        if (is_null($this->layout)) {
            if ($layout = $this->get_preferences('layout')) {
                $this->layout = layout_factory::build_layout($layout, $this);
            }

            // If still null default to grid layout.
            if (is_null($this->layout)) {
                $this->layout = new grid_layout($this);
            }
        }

        return $this->layout;
    }

    /**
     * Get context.
     *
     * @return \context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get template variables.
     *
     * @param \renderer_base $output
     * @return array|\renderer_base|\stdClass|string
     * @throws coding_exception
     */
    public final function export_for_template(\renderer_base $output) {
        $data = $this->get_layout()->export_for_template($output);
        $data['datasource'] = $this;
        return $data;
    }

    /**
     * Add form fields to the block edit form. IMPORTANT: Prefix field names with config_ otherwise the values will
     * not be saved.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        if ($form->get_tab() == preferences_form::TAB_GENERAL) {
            $mform->addElement('static', 'data_source_name', get_string('datasource', 'block_dash'), $this->get_name());

            $mform->addElement('select', 'config_preferences[layout]', get_string('layout', 'block_dash'),
                layout_factory::get_layout_form_options());
            $mform->setType('config_preferences[layout]', PARAM_TEXT);
        }

        if ($layout = $this->get_layout()) {
            $layout->build_preferences_form($form, $mform);
        }

        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            $sortablefielddefinitions = [];
            foreach ($this->get_available_field_definitions() as $fielddefinition) {
                if ($fielddefinition->get_option('supports_sorting') !== false) {
                    $sortablefielddefinitions[] = $fielddefinition;
                }
            }

            $mform->addElement('select', 'config_preferences[default_sort]', get_string('defaultsortfield', 'block_dash'),
                field_definition_factory::get_field_definition_options($sortablefielddefinitions));
            $mform->setType('config_preferences[default_sort]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[default_sort]', 'defaultsortfield', 'block_dash');
        }
    }

    #region Preferences

    /**
     * Get a specific preference.
     *
     * @param string $name
     * @return mixed|array
     */
    public final function get_preferences($name) {
        if ($this->preferences && isset($this->preferences[$name])) {
            return $this->preferences[$name];
        }

        return null;
    }

    /**
     * Get all preferences associated with the data source.
     *
     * @return array
     */
    public final function get_all_preferences() {
        return $this->preferences;
    }

    /**
     * Set preferences on this data source.
     *
     * @param array $preferences
     */
    public final function set_preferences(array $preferences) {
        $this->preferences = $preferences;
    }

    #endregion

    /**
     * Get count query template.
     *
     * @return string
     */
    public function get_count_query_template() {
        return $this->get_query_template();
    }

    /**
     * Get group by fields.
     *
     * @return string
     */
    public function get_groupby() {
        return false;
    }

    /**
     * Get available field definitions.
     *
     * @return field_definition_interface[]
     */
    public final function get_available_field_definitions() {
        if (is_null($this->fielddefinitions)) {
            $this->fielddefinitions = [];
            foreach ($this->build_available_field_definitions() as $fielddefinition) {
                $this->fielddefinitions[$fielddefinition->get_name()] = $fielddefinition;
            }
        }

        return $this->fielddefinitions;
    }

    /**
     * Check if report has a certain field
     *
     * @param string $name
     * @return bool
     */
    public function has_field_definition(string $name): bool {
        return isset($this->get_available_field_definitions()[$name]);
    }

    /**
     * Get field definition by name. Returns false if not found.
     *
     * @param string $name
     * @return bool|field_definition_interface
     */
    public function get_field_definition(string $name): ?field_definition_interface {
        // Field definitions are keyed by name.
        if ($this->has_field_definition($name)) {
            return $this->get_available_field_definitions()[$name];
        }

        return null;
    }

    /**
     * Get sorted field definitions based on preferences.
     *
     * @return sql_field_definition[]
     */
    public function get_sorted_field_definitions() {
        if (is_null($this->sortedfielddefinitions)) {
            $fielddefinitions = $this->get_available_field_definitions();;

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

            $this->sortedfielddefinitions = array_values($fielddefinitions);
        }

        return $this->sortedfielddefinitions;
    }

    /**
     * Get sorting.
     *
     * @throws coding_exception
     */
    public function get_sorting() {
        global $USER;

        if ($this->get_layout()->supports_sorting() && $this->get_block_instance()) {
            $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'sort');

            if ($cache->has($USER->id . '_' . $this->get_block_instance()->instance->id)) {
                return $cache->get($USER->id . '_' . $this->get_block_instance()->instance->id);
            }
        }

        if ($defaultsort = $this->get_preferences('default_sort')) {
            return [$defaultsort => 'asc'];
        }

        return [];
    }

    /**
     * Set block instance.
     *
     * @param \block_base $blockinstance
     */
    public function set_block_instance(\block_base $blockinstance) {
        $this->blockinstance = $blockinstance;
    }

    /**
     * Get block instance.
     *
     * @return null|\block_base
     */
    public function get_block_instance() {
        return $this->blockinstance;
    }
}
