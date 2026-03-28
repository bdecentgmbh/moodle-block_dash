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
 * Two stats layout design for reports.
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\dash_framework\structure\field_interface;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\data_source\form\preferences_form;

/**
 * Two stats layout design for reports.
 */
class two_stat_layout extends abstract_layout {
    /**
     * Get layout template filename.
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_two_stat';
    }

    /**
     * If the data source should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering() {
        return false;
    }

    /**
     * If the data source fields can be hidden or shown conditionally. It doesn't support visibility
     *
     * @return bool
     */
    public function supports_field_visibility() {
        return false;
    }

    /**
     * If the data source should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination() {
        return false;
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
     * @return mixed
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            $noneoption = [null => get_string('none', 'block_dash')];

            $mform->addElement('text', 'config_preferences[stat_field_label]', get_string('label', 'block_dash'));
            $mform->setType('config_preferences[stat_field_label]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[stat_field_definition]',
                get_string('stattodisplay', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[stat_field_definition]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[other_stat_field_definition]',
                get_string('stattodisplayother', 'block_dash'),
                array_merge(
                    $noneoption,
                    field_definition_factory::get_field_definition_options($this->get_data_source()->get_available_fields())
                )
            );
            $mform->setType('config_preferences[other_stat_field_definition]', PARAM_TEXT);
        }

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Modify objects before data is retrieved in the data source.
     */
    public function before_data() {
        parent::before_data();

        if (!$statfield = $this->get_data_source()->get_preferences('stat_field_definition')) {
            return;
        }

        if (!$otherstatfield = $this->get_data_source()->get_preferences('other_stat_field_definition')) {
            return;
        }

        foreach ($this->get_data_source()->get_available_fields() as $field) {
            $field->set_visibility(field_interface::VISIBILITY_HIDDEN);
        }

        if ($this->get_data_source()->has_field($statfield)) {
            $this->get_data_source()->get_field($statfield)
                ->set_visibility(field_interface::VISIBILITY_VISIBLE);
        }

        if ($this->get_data_source()->has_field($otherstatfield)) {
            $this->get_data_source()->get_field($otherstatfield)
                ->set_visibility(field_interface::VISIBILITY_VISIBLE);
        }
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
                'stat' => $this->get_data_source()->get_preferences('stat_field_definition'),
                'other_stat' => $this->get_data_source()->get_preferences('other_stat_field_definition'),
            ], $childcollection);
        }
        // Map details area fields + custom content (handled by parent).
        parent::after_data($datacollection);
    }
}
