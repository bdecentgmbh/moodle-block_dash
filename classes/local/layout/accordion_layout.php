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
 * Boostrap accordian layout for course format.
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
 * Boostrap accordian layout for course format.
 */
class accordion_layout extends abstract_layout {
    /**
     * Get layout template filename.
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_accordion';
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
        return true;
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
        if ($form->get_tab() == preferences_form::TAB_GENERAL) {
            $groupbyfields = [];
            $grouplabelfields = [];
            foreach ($this->get_data_source()->get_available_fields() as $key => $fielddefinition) {
                // Add secondary identifiers as choices to group results by. There's no point in grouping by the first
                // identifier, there would be no effect.
                if ($fielddefinition->has_attribute(identifier_attribute::class)) {
                    $groupbyfields[] = $fielddefinition;
                } else {
                    $grouplabelfields[] = $fielddefinition;
                }
            }

            $mform->addElement(
                'select',
                'config_preferences[groupby_field_definition]',
                get_string('groupby', 'block_dash'),
                field_definition_factory::get_field_definition_options($groupbyfields)
            );

            $mform->addElement(
                'select',
                'config_preferences[group_label_field_definition]',
                get_string('grouplabel', 'block_dash'),
                field_definition_factory::get_field_definition_options($grouplabelfields)
            );
        }

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Get data filter conditions.
     * @return data_strategy_interface
     */
    public function get_data_strategy() {
        if (!$groupbyfield = $this->get_data_source()->get_preferences('groupby_field_definition')) {
            return null;
        }
        if (!$groupbyfielddefinition = $this->get_data_source()->get_field($groupbyfield)) {
            return null;
        }

        if (!$grouplabelfield = $this->get_data_source()->get_preferences('group_label_field_definition')) {
            return null;
        }
        if (!$grouplabelfielddefinition = $this->get_data_source()->get_field($grouplabelfield)) {
            return null;
        }

        return new grouped_strategy($groupbyfielddefinition, $grouplabelfielddefinition);
    }
}
