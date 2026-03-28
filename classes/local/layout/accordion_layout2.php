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
 * Boostrap accordian layout2 for course format.
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\strategy\data_strategy_interface;
use block_dash\local\data_grid\data\strategy\grouped_strategy;
use block_dash\local\data_grid\data\strategy\standard_strategy;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\data_source\form\preferences_form;

/**
 * Boostrap accordian layout2 for course format.
 */
class accordion_layout2 extends accordion_layout {
    /**
     * Get layout template filename.
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_accordion2';
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination() {
        return false;
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
            $fielddefinitions = [];
            foreach ($this->get_data_source()->get_available_fields() as $fielddefinition) {
                if ($fielddefinition->has_attribute(identifier_attribute::class)) {
                    continue;
                }
                $fielddefinitions[] = $fielddefinition;
            }

            $noneoption = [null => get_string('none', 'block_dash')];

            $icons = array_flip($this->get_icon_list());
            asort($icons);
            $icons = $noneoption + $icons;

            $options = field_definition_factory::get_field_definition_options($fielddefinitions);

            $mform->addElement('html', '<hr>');

            $mform->addElement(
                'select',
                'config_preferences[field1]',
                get_string('accordionfield1', 'block_dash'),
                array_merge($noneoption, $options)
            );
            $mform->setType('config_preferences[field1]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[field1_icon]',
                get_string('accordionfield1icon', 'block_dash'),
                $icons
            );

            $mform->addElement('html', '<hr>');

            $mform->addElement(
                'select',
                'config_preferences[field2]',
                get_string('accordionfield2', 'block_dash'),
                array_merge($noneoption, $options)
            );
            $mform->setType('config_preferences[field2]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[field2_icon]',
                get_string('accordionfield2icon', 'block_dash'),
                $icons
            );

            $mform->addElement('html', '<hr>');

            $mform->addElement(
                'select',
                'config_preferences[field3]',
                get_string('accordionfield3', 'block_dash'),
                array_merge($noneoption, $options)
            );
            $mform->setType('config_preferences[field3]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[field3_icon]',
                get_string('accordionfield3icon', 'block_dash'),
                $icons
            );

            $mform->addElement('html', '<hr>');

            $mform->addElement(
                'select',
                'config_preferences[field4]',
                get_string('accordionfield4', 'block_dash'),
                array_merge($noneoption, $options)
            );
            $mform->setType('config_preferences[field4]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[field4_icon]',
                get_string('accordionfield4icon', 'block_dash'),
                $icons
            );
        }

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        global $OUTPUT;

        for ($i = 1; $i <= 4; $i++) {
            if (isset($preferences["field{$i}_icon"]) && !empty($preferences["field{$i}_icon"])) {
                if (block_dash_is_totara()) {
                    // Convert to flex icon output.
                    $preferences["field{$i}_icon"] = $OUTPUT->flex_icon($preferences["field{$i}_icon"]);
                } else {
                    $parts = explode(':', $preferences["field{$i}_icon"]);
                    if (count($parts) == 2) {
                        $preferences["field{$i}_icon"] = $OUTPUT->pix_icon($parts[1], '', $parts[0]);
                    }
                }
            }
        }

        return parent::process_preferences($preferences);
    }

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {
        foreach ($datacollection->get_child_collections('sections') as $childcollection) {
            foreach ($childcollection->get_child_collections('rows') as $row) {
                $this->map_data([
                    'field1' => $this->get_data_source()->get_preferences('field1'),
                    'field2' => $this->get_data_source()->get_preferences('field2'),
                    'field3' => $this->get_data_source()->get_preferences('field3'),
                    'field4' => $this->get_data_source()->get_preferences('field4'),
                ], $row);
            }
        }
        // Map details area fields + custom content (handled by parent).
        parent::after_data($datacollection);
    }
}
