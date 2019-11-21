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

use block_dash\data_grid\filter\condition;
use block_dash\data_grid\paginator;
use block_dash\template\template_interface;

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
     * The template used as a data/configuration source for this layout.
     *
     * @var template_interface
     */
    private $template;

    /**
     * @param template_interface $template
     */
    public function __construct(template_interface $template)
    {
        $this->template = $template;
    }

    /**
     * Get the template used as a data/configuration source for this layout.
     *
     * @return template_interface
     */
    public function get_template()
    {
        return $this->template;
    }

    /**
     * Modify objects before data is retrieved in the template. This allows the layout to make decisions on the
     * template and data grid.
     */
    public function before_data()
    {

    }

    /**
     * Modify objects after data is retrieved in the template. This allows the layout to make decisions on the
     * template and data grid.
     */
    public function after_data()
    {

    }

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the template. When a user chooses a layout, specific form elements may be
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
        $filter_collection = $this->get_template()->get_filter_collection();

        if ($this->supports_field_visibility()) {
            $group = [];
            foreach ($this->get_template()->get_available_field_definitions() as $available_field_definition) {
                $fieldname = 'config_preferences[available_fields][' . $available_field_definition->get_name() . '][visible]';
                $group[] = $mform->createElement('advcheckbox', $fieldname, $available_field_definition->get_title(), null,
                    ['group' => 1]);
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
            $mform->setType($fieldname, PARAM_BOOL);
        }
        $mform->addGroup($group, null, get_string('enabledconditions', 'block_dash'), ['<br>']);
        $form->add_checkbox_controller(3);
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
            'error' => ''
        ];

        try {
            $data = $this->get_template()->get_data();
        } catch (\Exception $e) {
            $error = \html_writer::tag('p', get_string('databaseerror', 'block_dash'));
            if (is_siteadmin()) {
                $error .= \html_writer::tag('p', $e->getMessage());
            }

            $templatedata['error'] .= $OUTPUT->notification($error, 'error');
        }

        $formhtml = $this->get_template()->get_filter_collection()->create_form_elements();

        if (isset($data)) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'data' => $data,
                'paginator' => $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_template()->get_data_grid()->get_paginator()
                    ->export_for_template($OUTPUT)),
                'supports_filtering' => $this->supports_filtering(),
                'supports_pagination' => $this->supports_pagination(),
                'preferences' => $this->get_template()->get_all_preferences()
            ]);
        }

        return $templatedata;
    }
}