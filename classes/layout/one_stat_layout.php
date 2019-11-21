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

use block_dash\data_grid\field\field_definition;

class one_stat_layout extends abstract_layout
{
    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        return 'block_dash/layout_stat';
    }

    /**
     * If the data source should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering()
    {
        return false;
    }

    /**
     * If the data source fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility()
    {
        return false;
    }

    /**
     * If the data source should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination()
    {
        return false;
    }

    /**
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @return mixed
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform)
    {
        $mform->addElement('text', 'config_preferences[stat_field_label]', get_string('label', 'block_dash'));
        $mform->setType('config_preferences[stat_field_label]', PARAM_TEXT);

        $options = [];
        foreach ($this->get_data_source()->get_available_field_definitions() as $field_definition) {
            $options[$field_definition->get_name()] = $field_definition->get_title();
        }

        $mform->addElement('select', 'config_preferences[stat_field_definition]',
            get_string('stattodisplay', 'block_dash'), $options);
        $mform->setType('config_preferences[stat_field_definition]', PARAM_TEXT);

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Modify objects before data is retrieved in the data source.
     */
    public function before_data()
    {
        parent::before_data();

        if (!$statfielddefinition = $this->get_data_source()->get_preferences('stat_field_definition')) {
            return;
        }

        foreach ($this->get_data_source()->get_data_grid()->get_field_definitions() as $field_definition) {
            $field_definition->set_visibility(field_definition::VISIBILITY_HIDDEN);
        }

        if ($this->get_data_source()->get_data_grid()->has_field_definition($statfielddefinition)) {
            $this->get_data_source()->get_data_grid()->get_field_definition($statfielddefinition)
                ->set_visibility(field_definition::VISIBILITY_VISIBLE);
        }
    }
}