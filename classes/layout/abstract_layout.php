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

namespace block_dash\layout;

use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\data\strategy\data_strategy_interface;
use block_dash\data_grid\data\strategy\standard_strategy;
use block_dash\data_grid\field\attribute\identifier_attribute;
use block_dash\data_grid\filter\condition;
use block_dash\data_grid\paginator;
use block_dash\data_source\data_source_interface;
use core\output\icon_system;
use core\output\icon_system_fontawesome;

/**
 * Extend this class when creating new layouts.
 *
 * Then register the layout in a lib.php function: pluginname_register_layouts(). See blocks/dash/lib.php for an
 * example.
 *
 *
 * @package block_dash\layout
 */
abstract class abstract_layout implements layout_interface, \templatable
{
    /**
     * @var int Used for creating unique checkbox controller group IDs.
     */
    private static $currentgroupid = null;

    /**
     * The data source used as a data/configuration source for this layout.
     *
     * @var data_source_interface
     */
    private $datasource;

    /**
     * @param data_source_interface $datasource
     */
    public function __construct(data_source_interface $datasource)
    {
        $this->datasource = $datasource;
    }

    /**
     * Get the data source used as a data/configuration source for this layout.
     *
     * @return data_source_interface
     */
    public function get_data_source()
    {
        return $this->datasource;
    }

    /**
     * @return data_strategy_interface
     */
    public function get_data_strategy()
    {
        return new standard_strategy();
    }

    /**
     * Modify objects before data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     */
    public function before_data()
    {

    }

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $data_collection
     */
    public function after_data(data_collection_interface $data_collection)
    {

    }

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the data source. When a user chooses a layout, specific form elements may be
     * displayed after a quick refresh of the form.
     *
     * Be sure to call parent::build_preferences_form() if you override this method.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform)
    {
        global $OUTPUT;

        self::$currentgroupid = random_int(1, 10000);

        $filter_collection = $this->get_data_source()->get_filter_collection();

        if ($this->supports_field_visibility()) {
            $group = [];
            foreach ($this->get_data_source()->get_sorted_field_definitions() as $available_field_definition) {
                if ($available_field_definition->has_attribute(identifier_attribute::class)) {
                    continue;
                }

                $fieldname = 'config_preferences[available_fields][' . $available_field_definition->get_name() .
                    '][visible]';

                $tablenames = [];
                if ($tables = $available_field_definition->get_option('tables')) {
                    foreach ($tables as $table) {
                        $tablenames[] = get_string('tablealias_' . $table, 'block_dash');
                    }
                }

                if ($tablenames) {
                    $title = implode(', ', $tablenames);
                } else {
                    $title = get_string('general');
                }

                $icon = $OUTPUT->pix_icon('i/dragdrop', get_string('dragitem', 'block_dash'), 'moodle', ['class' => 'drag-handle']);
                $title = $icon . '<b>' . $title . '</b>: ' . $available_field_definition->get_title();

                $group[] = $mform->createElement('advcheckbox', $fieldname, $title, null, ['group' => self::$currentgroupid]);
                $mform->setType($fieldname, PARAM_BOOL);
            }
            $mform->addGroup($group, null, get_string('enabledfields', 'block_dash'),
                ['<div style="width: 100%;"></div>']);
            $form->add_checkbox_controller(self::$currentgroupid);

            self::$currentgroupid++;
        }

        if ($this->supports_filtering()) {
            $group = [];
            foreach ($filter_collection->get_filters() as $filter) {
                if ($filter instanceof condition) {
                    // Don't include conditions in this group.
                    continue;
                }
                $fieldname = 'config_preferences[filters][' . $filter->get_name() . '][enabled]';
                $group[] = $mform->createElement('advcheckbox', $fieldname, $filter->get_label(), null,
                    ['group' => self::$currentgroupid]);
                $mform->setType($fieldname, PARAM_BOOL);
            }
            $mform->addGroup($group, null, get_string('enabledfilters', 'block_dash'),
                ['<div style="width: 100%;"></div>']);
            $form->add_checkbox_controller(self::$currentgroupid);

            self::$currentgroupid++;
        }

        $group = [];
        foreach ($filter_collection->get_filters() as $filter) {
            if (!$filter instanceof condition) {
                // Only include conditions in this group.
                continue;
            }
            $fieldname = 'config_preferences[filters][' . $filter->get_name() . '][enabled]';
            $group[] = $mform->createElement('advcheckbox', $fieldname, $filter->get_label(), null,
                ['group' => self::$currentgroupid]);
            $mform->setType($fieldname, PARAM_BOOL);
        }
        $mform->addGroup($group, null, get_string('enabledconditions', 'block_dash'),
            ['<div style="width: 100%;"></div>']);
        $form->add_checkbox_controller(self::$currentgroupid);

        self::$currentgroupid++;
    }

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences)
    {
        return $preferences;
    }

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(\renderer_base $output)
    {
        global $OUTPUT;

        $templatedata = [
            'error' => '',
            'paginator' => '',
            'data' => null,
            'uniqueid' => uniqid()
        ];

        if (!empty($this->get_data_source()->get_all_preferences())) {
            try {
                $templatedata['data'] = $this->get_data_source()->get_data();
            } catch (\Exception $e) {
                $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
                if (is_siteadmin()) {
                    $error .= \html_writer::tag('p', $e->getMessage());
                }

                $templatedata['error'] .= $OUTPUT->notification($error, 'error');
            }

            if ($this->get_data_source()->get_data_grid()->get_paginator()->get_page_count() > 1) {
                $templatedata['paginator'] = $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_data_source()->get_data_grid()->get_paginator()
                    ->export_for_template($OUTPUT));
            }
        }

        $formhtml = $this->get_data_source()->get_filter_collection()->create_form_elements();

        if (!is_null($templatedata['data'])) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'supports_filtering' => $this->supports_filtering(),
                'supports_pagination' => $this->supports_pagination(),
                'preferences' => $this->process_preferences($this->get_data_source()->get_all_preferences())
            ]);
        }

        return $templatedata;
    }

    protected function map_data($mapping, data_collection_interface $data_collection)
    {
        foreach ($mapping as $newname => $fieldname) {
            if ($fieldname) {
                $data_collection->add_data_associative([
                    $newname => $data_collection[$fieldname]
                ]);
            }
        }

        return $data_collection;
    }

    protected function get_icon_list()
    {
        global $PAGE;

        $icons = [];

        if (isset($PAGE->theme->iconsystem)) {
            if ($icon_system = icon_system::instance($PAGE->theme->iconsystem)) {
                if ($icon_system instanceof icon_system_fontawesome) {
                    foreach ($icon_system->get_icon_name_map() as $pixname => $faname) {
                        $icons[$faname] = $pixname;
                    }
                }
            }
        }

        return $icons;
    }
}