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
 * Timeline layout for reports builder.
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\field\attribute\image_url_attribute;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\paginator;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\data_grid\field\attribute\color_attribute;

/**
 * Timeline layout for reports builder.
 */
class timeline_layout extends abstract_layout {
    /**
     * Get layout template filename.
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_timeline';
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination() {
        return true;
    }

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility() {
        return false;
    }

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering() {
        return true;
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
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            $imageurlfields = [];
            foreach ($this->get_data_source()->get_available_fields() as $field) {
                if ($field->has_attribute(image_url_attribute::class)) {
                    $imageurlfields[] = $field;
                }
            }

            $noneoption = [null => get_string('none', 'block_dash')];

            $mform->addElement(
                'select',
                'config_preferences[iconfield]',
                get_string('iconfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[iconfield]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[subheadingfield]',
                get_string('subheadingfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[subheadingfield]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[subheadingfield]', 'subheadingfield', 'block_dash');

            $icons = array_flip($this->get_icon_list());
            asort($icons);
            $icons = $noneoption + $icons;

            $mform->addElement(
                'select',
                'config_preferences[subheadingfield_icon]',
                get_string('subheadingfieldicon', 'block_dash'),
                $icons
            );
            $mform->setDefault('config_preferences[subheadingfield_icon]', 'fa-clock-o');

            $colorfields = [];
            foreach ($this->get_data_source()->get_available_fields() as $field) {
                // Check for color_attribute from block_dash.
                if ($field->has_attribute(color_attribute::class)) {
                    $colorfields[] = $field;
                    continue;
                }
                // Check for event_color_attribute from local_dash if available.
                if (
                    class_exists('local_dash\\data_grid\\field\\attribute\\event\\event_color_attribute')
                    && $field->has_attribute('local_dash\\data_grid\\field\\attribute\\event\\event_color_attribute')
                ) {
                    $colorfields[] = $field;
                    continue;
                }
                // Also check for the local_dash color_attribute for backward compatibility.
                if (
                    class_exists('local_dash\\data_grid\\field\\attribute\\color_attribute')
                    && $field->has_attribute('local_dash\\data_grid\\field\\attribute\\color_attribute')
                ) {
                    $colorfields[] = $field;
                }
            }

            $mform->addElement(
                'select',
                'config_preferences[badgecolorfield]',
                get_string('badgecolorfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options($colorfields))
            );
            $mform->addHelpButton('config_preferences[badgecolorfield]', 'badgecolorfield', 'block_dash');

            $mform->addElement(
                'select',
                'config_preferences[headingfield]',
                get_string('headingfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[headingfield]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[bodyfield]',
                get_string('bodyfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[bodyfield]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[bodyfield2]',
                get_string('bodyfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[bodyfield2]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[bodyfield3]',
                get_string('bodyfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[bodyfield3]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[imageurlfield]',
                get_string('imageurlfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $imageurlfields
                ))
            );
            $mform->setType('config_preferences[imageurlfield]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[imageurlfield]', 'imageurlfield', 'block_dash');
        }

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Modify objects before data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     */
    public function before_data() {
        parent::before_data();

        if (!$perpage = $this->get_data_source()->get_preferences('perpage')) {
            $perpage = paginator::PER_PAGE_DEFAULT;
        }
        $this->get_data_source()->get_paginator()->set_per_page((int)$perpage);
    }

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {

        foreach ($datacollection->get_child_collections('rows') as $childcollection) {
            $this->map_data([
                    'icon' => $this->get_data_source()->get_preferences('iconfield'),
                    'badgecolor' => $this->get_data_source()->get_preferences('badgecolorfield'),
                    'heading' => $this->get_data_source()->get_preferences('headingfield'),
                    'subheading' => $this->get_data_source()->get_preferences('subheadingfield'),
                    'body' => $this->get_data_source()->get_preferences('bodyfield'),
                    'body2' => $this->get_data_source()->get_preferences('bodyfield2'),
                    'body3' => $this->get_data_source()->get_preferences('bodyfield3'),
                    'imageurl' => $this->get_data_source()->get_preferences('imageurlfield'),
                ], $childcollection);
        }
        // Map details area fields + custom content (handled by parent).
        parent::after_data($datacollection);
    }

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        global $OUTPUT;

        if (isset($preferences["subheadingfield_icon"]) && !empty($preferences["subheadingfield_icon"])) {
            if (block_dash_is_totara()) {
                // Convert to flex icon output.
                $preferences["subheadingfield_icon"] = $OUTPUT->flex_icon($preferences["subheadingfield_icon"]);
            } else {
                $parts = explode(':', $preferences["subheadingfield_icon"]);
                if (count($parts) == 2) {
                    $preferences["subheadingfield_icon"] = $OUTPUT->pix_icon($parts[1], '', $parts[0]);
                }
            }
        }

        return parent::process_preferences($preferences);
    }
}
