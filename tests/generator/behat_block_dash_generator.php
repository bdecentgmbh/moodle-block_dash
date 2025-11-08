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
 * Block dash instance test instance generate defined.
 *
 * @package   block_dash
 * @copyright 2024, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\data_source\data_source_factory;

/**
 * Dash Block behat data generator.
 */
class behat_block_dash_generator extends behat_generator_base {
    /**
     * Get a list of the entities that can be created.
     *
     * @return array entity name => information about how to generate.
     */
    protected function get_creatable_entities(): array {

        return [
            'dash blocks' => [
                'singular' => 'dash block',
                'datagenerator' => 'dash_block',
                'required' => ['type', 'name'],
                'switchids' => ['pageid' => 'pageid'],
            ],

            'dash blocks default' => [
                'singular' => 'dash block default',
                'datagenerator' => 'dash_block_default',
                'required' => ['type', 'name'],
                'switchids' => [],
            ],
        ];
    }

    /**
     * Preprocess dash block data before creation.
     * @param array $data
     * @return array
     */
    protected function preprocess_dash_block(array $data): array {
        // Validate required fields.
        if (empty($data['type']) || !$this->is_valid_dash_type($data['type'])) {
            throw new InvalidArgumentException('Invalid or missing dash block type.');
        }

        $datasource = $this->is_valid_dash_name($data['type'], $data['name']);
        if (!$datasource || !class_exists($datasource)) {
            throw new InvalidArgumentException('Invalid dash block name.');
        }

        $preferences['config_preferences'] = [];
        $preferences['config_data_source_idnumber'] = $datasource;

        $config = new stdClass();
        $config->data_source_idnumber = $datasource;
        $context = context_system::instance();
        $datasource = data_source_factory::build_data_source($config->data_source_idnumber, $context);
        if ($datasource) {
            if (method_exists($datasource, 'set_default_preferences')) {
                $datasource->set_default_preferences($preferences);
            }
        }

        if (isset($data['fields'])) {
            // List of fields to enable.
            $fields = explode(',', $data['fields']);
            $datafields = array_map('trim', $fields);
            $availablefields = [];

            $disabledfields = explode(',', $data['disablefields'] ?? '');
            $disabledfields = array_map('trim', $disabledfields);

            if ($data['fields'] == 'all') {
                foreach ($datasource->get_available_fields() as $field) {
                    $isdisabled = in_array($field->get_name(), $disabledfields)
                        || in_array($field->get_title()->out(), $disabledfields) || in_array($field->get_alias(), $disabledfields);
                    if ($isdisabled) {
                        continue;
                    }
                    $availablefields[$field->get_alias()] = ['visible' => 1];
                }
            } else {
                $fieldslookup = [];
                foreach ($datasource->get_available_fields() as $field) {
                    if (
                        in_array($field->get_name(), $disabledfields) ||
                        in_array($field->get_title()->out(), $disabledfields) ||
                        in_array($field->get_alias(), $disabledfields)
                    ) {
                        continue;
                    }

                    $fieldslookup[$field->get_name()] = $field;
                    $fieldslookup[$field->get_title()->out()] = $field;
                    $fieldslookup[$field->get_alias()] = $field;
                }

                foreach ($datafields as $requestedfield) {
                    if (isset($fieldslookup[$requestedfield])) {
                        $field = $fieldslookup[$requestedfield];
                        $availablefields[$field->get_alias()] = ['visible' => 1];
                    }
                }
            }
            // Available fields need to be merged.
            $preferences['config_preferences']['available_fields'] = $availablefields;
        }

        if (isset($data['filters'])) {
            $datafilters = explode(',', $data['filters']);
            $datafilters = array_map(fn($v) => strtolower(trim($v)), $datafilters);
            $filtercollection = $datasource->get_filter_collection();
            $filters = [];
            foreach ($filtercollection->get_filters() as $key => $filter) {
                if (
                    $data['filters'] == 'all' || in_array($filter->get_name(), $datafilters)
                    || in_array(strtolower($filter->get_label()), $datafilters)
                ) {
                    $filters[$filter->get_name()] = ['enabled' => 1];
                }
            }
            $preferences['config_preferences']['filters'] = $filters;
        }

        if (isset($data['perpage'])) {
            $preferences['config_preferences']['perpage'] = (int)$data['perpage'];
        }

        $config->preferences = $preferences['config_preferences'] ?? [];

        if (isset($data['title'])) {
            $config->title = $data['title'];
        }

        $data['configdata'] = $config;

        return $data;
    }

    /**
     * Preprocess dash block default data before creation.
     * @param array $data
     * @return array
     */
    protected function preprocess_dash_block_default(array $data): array {
        $data = $this->preprocess_dash_block($data);

        return $data;
    }

    /**
     * Validate dash type. it can be datasource or widget.
     *
     * @param string $type
     * @return string
     */
    protected function is_valid_dash_type(string $type): string {
        $dashtypes = ['datasource', 'widget'];
        return in_array($type, $dashtypes);
    }

    /**
     * Validate dash name.
     *
     * @param string $dashtype
     * @param string $name
     * @return string|false
     */
    protected function is_valid_dash_name(string $dashtype, string $name): string|false {
        $options = \block_dash\local\data_source\data_source_factory::get_data_source_form_options();
        foreach ($options as $optionname => $optionlabel) {
            if ($dashtype === 'datasource' && str_ends_with($optionname, $name . '_data_source')) {
                return $optionname;
            }
        }
        return false;
    }
}
