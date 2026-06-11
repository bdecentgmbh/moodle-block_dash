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
 * Boostrap cards layout for course format.
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\paginator;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field as data_field;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\data_grid\field\attribute\image_attribute;
use block_dash\local\data_grid\field\attribute\image_url_attribute;
use block_dash\local\data_grid\field\attribute\linked_data_attribute;

/** Edit enrolment action. */
define('CARD_LAYOUT_SLIDER_MODE', 'slider');

/** Unenrol action. */
define('CARD_LAYOUT_MASONRY_MODE', 'masonry');

/** Unenrol action. */
define('CARD_LAYOUT_NORMAL_MODE', 'none');



/**
 * Boostrap cards layout for course format.
 */
class cards_layout extends abstract_layout {
    /**
     * Get layout template filename.
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_cards';
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
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        global $OUTPUT, $PAGE;
        // Default to grid mode when no layoutmode is stored.
        if (empty($preferences['layoutmode'])) {
            $preferences['layoutmode'] = CARD_LAYOUT_NORMAL_MODE;
        }
        if (isset($preferences['layoutmode'])) {
            if ($preferences['layoutmode'] == CARD_LAYOUT_SLIDER_MODE) {
                $sliderclass = '';
                $preferences['layout_slider'] = true;
                $preferences['autoplay'] = ($preferences['autoplay']) ? 'true' : 'false';
                $preferences['arrows'] = ($preferences['arrows']) ? 'true' : 'false';
                $sliderclass .= ($preferences['centerMode']) ? 'slider-center-mode' : '';
                $preferences['centerMode'] = ($preferences['centerMode']) ? 'true' : 'false';
                $preferences['dots'] = ($preferences['dots']) ? 'true' : 'false';
                $preferences['draggable'] = ($preferences['draggable']) ? 'true' : 'false';
                $preferences['fade'] = ($preferences['fade']) ? 'true' : 'false';
                $preferences['infinite'] = ($preferences['infinite']) ? 'true' : 'false';
                $preferences['swipeToSlide'] = ($preferences['swipeToSlide']) ? 'true' : 'false';
                $sliderclass .= ($preferences['variableWidth']) ? ' slider-variable-mode' : '';
                $sliderclass .= ($preferences['rows'] == 1) ? ' slider-n-rows' : '';
                $preferences['variableWidth'] = ($preferences['variableWidth']) ? 'true' : 'false';
                $preferences['vertical'] = ($preferences['vertical']) ? 'true' : 'false';
                $preferences['verticalSwiping'] = ($preferences['verticalSwiping']) ? 'true' : 'false';
                $preferences['centerPadding'] = isset($preferences['centerPadding']) ?
                    intval($preferences['centerPadding']) : '';
                $preferences['sliderclass'] = $sliderclass;
            } else if ($preferences['layoutmode'] == CARD_LAYOUT_MASONRY_MODE) {
                $preferences['layout_masonry'] = true;
                $preferences['masonrysearch'] = isset($preferences['masonrysearch']) &&
                    ($preferences['masonrysearch']) ? true : false;
                $preferences['masonrysort'] = isset($preferences['masonrysort']) &&
                    ($preferences['masonrysort']) ? true : false;
            } else if ($preferences['layoutmode'] == CARD_LAYOUT_NORMAL_MODE) {
                $preferences['layout_default'] = true;
            }

            // Column classes.
            $preferences['columnsclass'] = isset($preferences['columns']) ?
                block_dash_get_card_column_customclass($preferences['columns']) : '';

            // Columns.
            $preferences['columns'] = $preferences['columns'] ?? 1;
        }
        return parent::process_preferences($preferences);
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
        // Layout tab: columns and styling options.
        if ($form->get_tab() == preferences_form::TAB_GENERAL) {
            $this->build_tab_general($form, $mform);
        }

        // Fields tab: card field mapping (image, heading, body, footer, etc.).
        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            // Normal grid item field mapping.
            $imageurlfields = [];
            foreach ($this->get_data_source()->get_available_fields() as $field) {
                if ($field->has_attribute(image_url_attribute::class) && !$field->has_attribute(linked_data_attribute::class)) {
                    $imageurlfields[] = $field;
                }
            }

            $courseimageurlfields = [];
            foreach ($this->get_data_source()->get_available_fields() as $field) {
                if ($field->has_attribute(image_attribute::class)) {
                    $courseimageurlfields[] = $field;
                }
            }

            $noneoption = [null => get_string('none', 'block_dash')];

            $mform->addElement(
                'select',
                'config_preferences[backgroundimagefield]',
                get_string('backgroundimagefield', 'block_dash'),
                array_merge(
                    $noneoption,
                    field_definition_factory::get_field_definition_options($imageurlfields)
                )
            );
            $mform->setType('config_preferences[backgroundimagefield]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[backgroundimagefield]', 'backgroundimagefield', 'block_dash');

            $mform->addElement(
                'select',
                'config_preferences[imageurlfield]',
                get_string('imageurlfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $courseimageurlfields
                ))
            );
            $mform->setType('config_preferences[imageurlfield]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[imageurlfield]', 'imageurlfield', 'block_dash');

            $mform->addElement(
                'select',
                'config_preferences[imageoverlayfield]',
                get_string('imageoverlayfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[imageoverlayfield]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[imageoverlayfield]', 'imageoverlayfield', 'block_dash');

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
                'config_preferences[body2field]',
                get_string('bodyfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[body2field]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[body3field]',
                get_string('bodyfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[body3field]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[footerfield]',
                get_string('footerfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[footerfield]', PARAM_TEXT);

            $mform->addElement(
                'select',
                'config_preferences[footerrightfield]',
                get_string('footerrightfield', 'block_dash'),
                array_merge($noneoption, field_definition_factory::get_field_definition_options(
                    $this->get_data_source()->get_available_fields()
                ))
            );
            $mform->setType('config_preferences[footerrightfield]', PARAM_TEXT);
        }

        parent::build_preferences_form($form, $mform);
    }

    /**
     * Build the Layout tab (TAB_GENERAL) form fields for this layout variant.
     * Subclasses override this method to show different options (slider/masonry etc.).
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    protected function build_tab_general(\moodleform $form, \MoodleQuickForm $mform) {
        // Search box.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysearch]', get_string('strmasonrysearch', 'block_dash'));
        $mform->setType('config_preferences[masonrysearch]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysearch]', false);
        // Sort.
        $mform->addElement('advcheckbox', 'config_preferences[masonrysort]', get_string('strmasonrysort', 'block_dash'));
        $mform->setType('config_preferences[masonrysort]', PARAM_BOOL);
        $mform->setDefault('config_preferences[masonrysort]', false);
        // Columns (number of items per row).
        $mform->addElement('select', 'config_preferences[columns]', get_string('columns', 'block_dash'), [
            12 => 1, 6 => 2, 4 => 3, 3 => 4, 25 => 5, 2 => 6, 1 => 12,
        ]);
        $mform->setType('config_preferences[columns]', PARAM_INT);
        $mform->addHelpButton('config_preferences[columns]', 'columns', 'block_dash');
        $mform->setDefault('config_preferences[columns]', 3);

        // Styling options (custom-field-based CSS classes applied to each card).
        $this->add_layout_styles_field($mform);
    }

    /**
     * Add the layout styles multi-select field to the form.
     *
     * Builds a list of custom field options from course or user profile fields
     * and adds a multi-select element for choosing CSS class styling.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function add_layout_styles_field(\MoodleQuickForm $mform) {
        $options = [];
        if (
            in_array("dashaddon_courses\local\dash_framework\structure\course_table", array_map(
                'get_class',
                $this->get_data_source()->get_tables()
            ))
        ) {
            if (class_exists('\core_course\customfield\course_handler')) {
                $handler = \core_course\customfield\course_handler::create();
                $fields = $handler->get_fields();
                foreach ($fields as $field) {
                    $alias = 'c_f_' . strtolower($field->get('shortname'));
                    $options[$alias] = format_string($field->get_formatted_name());
                }
            }
        } else if (
            in_array("block_dash\local\dash_framework\structure\user_table", array_map(
                'get_class',
                $this->get_data_source()->get_tables()
            ))
        ) {
            $fields = profile_get_custom_fields();
            foreach ($fields as $field) {
                $alias = 'u_pf_' . strtolower($field->shortname);
                $options[$alias] = format_string($field->name);
            }
        }
        $select = $mform->addElement(
            'select',
            'config_preferences[layoutstyles]',
            get_string('styleoptions', 'block_dash'),
            $options,
            ['class' => 'select2-form']
        );
        $mform->addHelpButton('config_preferences[layoutstyles]', 'styleoptions', 'block_dash');
        $select->setMultiple(true);
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
                'bgimageurl' => $this->get_data_source()->get_preferences('backgroundimagefield'),
                'imageurl' => $this->get_data_source()->get_preferences('imageurlfield'),
                'imageoverlay' => $this->get_data_source()->get_preferences('imageoverlayfield'),
                'heading' => $this->get_data_source()->get_preferences('headingfield'),
                'subheading' => $this->get_data_source()->get_preferences('subheadingfield'),
                'body' => $this->get_data_source()->get_preferences('bodyfield'),
                'body2' => $this->get_data_source()->get_preferences('body2field'),
                'body3' => $this->get_data_source()->get_preferences('body3field'),
                'footer' => $this->get_data_source()->get_preferences('footerfield'),
                'footerright' => $this->get_data_source()->get_preferences('footerrightfield'),
                'tablesort' => $this->get_data_source()->get_preferences('default_sort'),
                'stylingoptions' => $this->get_data_source()->get_preferences('layoutstyles'),
            ], $childcollection);
        }
        // Map details area fields + custom content (handled by parent).
        parent::after_data($datacollection);
    }
}
