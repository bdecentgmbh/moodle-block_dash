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
 * Cards masonry layout — a cards_layout variant that forces masonry mode.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

/**
 * Cards masonry layout: renders cards in a Masonry grid arrangement.
 */
class cards_masonry_layout extends cards_layout {
    /**
     * Masonry mode does not use pagination.
     *
     * @return bool
     */
    public function supports_pagination() {
        return false;
    }

    /**
     * Force layoutmode to masonry before delegating to parent processing.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        $preferences['layoutmode'] = CARD_LAYOUT_MASONRY_MODE;
        return parent::process_preferences($preferences);
    }

    /**
     * Build the Layout tab form fields specific to masonry mode.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    protected function build_tab_general(\moodleform $form, \MoodleQuickForm $mform) {
        // Masonry options.

        // Search box toggle.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysearch]', get_string('strmasonrysearch', 'block_dash'));
        $mform->setType('config_preferences[masonrysearch]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysearch]', false);

        // Sort toggle.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysort]', get_string('strmasonrysort', 'block_dash'));
        $mform->setType('config_preferences[masonrysort]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysort]', false);

        // Styling options (CSS class fields from custom fields).
        $this->add_layout_styles_field($mform);
    }
}
