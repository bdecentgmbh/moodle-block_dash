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
 * Cards slider layout — a cards_layout variant that forces slider mode.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

/**
 * Cards slider layout: renders cards as a horizontal Slick slider.
 */
class cards_slider_layout extends cards_layout {
    /**
     * Slider mode does not use pagination.
     *
     * @return bool
     */
    public function supports_pagination() {
        return false;
    }

    /**
     * Force layoutmode to slider before delegating to parent processing.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        $preferences['layoutmode'] = CARD_LAYOUT_SLIDER_MODE;
        return parent::process_preferences($preferences);
    }

    /**
     * Build the Layout tab form fields specific to slider mode.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    protected function build_tab_general(\moodleform $form, \MoodleQuickForm $mform) {
        // Search.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysearch]', get_string('strmasonrysearch', 'block_dash'));
        $mform->setType('config_preferences[masonrysearch]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysearch]', false);

        // Sort.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysort]', get_string('strmasonrysort', 'block_dash'));
        $mform->setType('config_preferences[masonrysort]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysort]', false);
        // Slider options.
        $mform->addElement('advcheckbox', 'config_preferences[autoplay]', get_string('autoplay', 'block_dash'));
        $mform->setType('config_preferences[autoplay]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[autoplay]', 'autoplay', 'block_dash');
        $mform->setDefault('config_preferences[autoplay]', false);

        $mform->addElement('text', 'config_preferences[autoplaySpeed]', get_string('autoplaySpeed', 'block_dash'));
        $mform->setType('config_preferences[autoplaySpeed]', PARAM_INT);
        $mform->addHelpButton('config_preferences[autoplaySpeed]', 'autoplaySpeed', 'block_dash');
        $mform->setDefault('config_preferences[autoplaySpeed]', 3000);

        $mform->addElement('advcheckbox', 'config_preferences[arrows]', get_string('arrows', 'block_dash'));
        $mform->setType('config_preferences[arrows]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[arrows]', 'arrows', 'block_dash');
        $mform->setDefault('config_preferences[arrows]', true);

        $mform->addElement('advcheckbox', 'config_preferences[centerMode]', get_string('centerMode', 'block_dash'));
        $mform->setType('config_preferences[centerMode]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[centerMode]', 'centerMode', 'block_dash');
        $mform->setDefault('config_preferences[centerMode]', false);

        $mform->addElement('text', 'config_preferences[centerPadding]', get_string('centerPadding', 'block_dash'));
        $mform->setType('config_preferences[centerPadding]', PARAM_INT);
        $mform->addHelpButton('config_preferences[centerPadding]', 'centerPadding', 'block_dash');
        $mform->setDefault('config_preferences[centerPadding]', 50);

        $mform->addElement('advcheckbox', 'config_preferences[dots]', get_string('dots', 'block_dash'));
        $mform->setType('config_preferences[dots]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[dots]', 'dots', 'block_dash');
        $mform->setDefault('config_preferences[dots]', false);

        $mform->addElement('advcheckbox', 'config_preferences[draggable]', get_string('draggable', 'block_dash'));
        $mform->setType('config_preferences[draggable]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[draggable]', 'draggable', 'block_dash');
        $mform->setDefault('config_preferences[draggable]', true);

        $mform->addElement('advcheckbox', 'config_preferences[fade]', get_string('fade', 'block_dash'));
        $mform->setType('config_preferences[fade]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[fade]', 'fade', 'block_dash');
        $mform->setDefault('config_preferences[fade]', false);

        $mform->addElement('advcheckbox', 'config_preferences[infinite]', get_string('infinite', 'block_dash'));
        $mform->setType('config_preferences[infinite]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[infinite]', 'infinite', 'block_dash');
        $mform->setDefault('config_preferences[infinite]', true);

        $mform->addElement(
            'select',
            'config_preferences[rows]',
            get_string('rows', 'block_dash'),
            array_combine(range(1, 10), range(1, 10))
        );
        $mform->setType('config_preferences[rows]', PARAM_INT);
        $mform->addHelpButton('config_preferences[rows]', 'rows', 'block_dash');
        $mform->setDefault('config_preferences[rows]', 1);

        $mform->addElement(
            'select',
            'config_preferences[slidesPerRow]',
            get_string('slidesPerRow', 'block_dash'),
            array_combine(range(1, 10), range(1, 10))
        );
        $mform->setType('config_preferences[slidesPerRow]', PARAM_INT);
        $mform->addHelpButton('config_preferences[slidesPerRow]', 'slidesPerRow', 'block_dash');
        $mform->setDefault('config_preferences[slidesPerRow]', 1);

        $mform->addElement('text', 'config_preferences[slidesToShow]', get_string('slidesToShow', 'block_dash'));
        $mform->setType('config_preferences[slidesToShow]', PARAM_INT);
        $mform->addHelpButton('config_preferences[slidesToShow]', 'slidesToShow', 'block_dash');
        $mform->setDefault('config_preferences[slidesToShow]', 1);

        $mform->addElement('text', 'config_preferences[slidesToScroll]', get_string('slidesToScroll', 'block_dash'));
        $mform->setType('config_preferences[slidesToScroll]', PARAM_INT);
        $mform->addHelpButton('config_preferences[slidesToScroll]', 'slidesToScroll', 'block_dash');
        $mform->setDefault('config_preferences[slidesToScroll]', 1);

        $mform->addElement('text', 'config_preferences[speed]', get_string('speed', 'block_dash'));
        $mform->setType('config_preferences[speed]', PARAM_INT);
        $mform->addHelpButton('config_preferences[speed]', 'speed', 'block_dash');
        $mform->setDefault('config_preferences[speed]', 300);

        $mform->addElement('advcheckbox', 'config_preferences[swipeToSlide]', get_string('swipeToSlide', 'block_dash'));
        $mform->setType('config_preferences[swipeToSlide]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[swipeToSlide]', 'swipeToSlide', 'block_dash');
        $mform->setDefault('config_preferences[swipeToSlide]', false);

        $mform->addElement('advcheckbox', 'config_preferences[variableWidth]', get_string('variableWidth', 'block_dash'));
        $mform->setType('config_preferences[variableWidth]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[variableWidth]', 'variableWidth', 'block_dash');
        $mform->setDefault('config_preferences[variableWidth]', false);

        $mform->addElement('advcheckbox', 'config_preferences[vertical]', get_string('vertical', 'block_dash'));
        $mform->setType('config_preferences[vertical]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[vertical]', 'vertical', 'block_dash');
        $mform->setDefault('config_preferences[vertical]', false);

        $mform->addElement('advcheckbox', 'config_preferences[verticalSwiping]', get_string('verticalSwiping', 'block_dash'));
        $mform->setType('config_preferences[verticalSwiping]', PARAM_BOOL);
        $mform->addHelpButton('config_preferences[verticalSwiping]', 'verticalSwiping', 'block_dash');
        $mform->setDefault('config_preferences[verticalSwiping]', false);

        // Styling options (CSS class fields from custom fields).
        $this->add_layout_styles_field($mform);
    }
}
