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
 * Extend this class when creating new layouts.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\field;
use block_dash\local\data_grid\data\strategy\data_strategy_interface;
use block_dash\local\data_grid\data\strategy\standard_strategy;
use block_dash\local\data_grid\field\attribute\context_attribute;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\field\field_definition_factory;
use block_dash\local\dash_framework\structure\details_area_table;
use block_dash\local\paginator;
use block_dash\local\data_source\data_source_interface;
use block_dash\local\data_source\form\preferences_form;
use html_writer;
use moodle_url;

/**
 * Extend this class when creating new layouts.
 *
 * Then register the layout in a lib.php function: pluginname_register_layouts(). See blocks/dash/lib.php for an
 * example.
 *
 * @package block_dash
 */
abstract class abstract_layout implements layout_interface, \templatable {
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
     * Layout constructor.
     *
     * @param data_source_interface $datasource
     */
    public function __construct(data_source_interface $datasource) {
        $this->datasource = $datasource;
        // Inject the details area table so every data source offers details button/link fields.
        // Pass the block instance ID so detail IDs are unique when multiple blocks are on one page.
        $blockinstanceid = 0;
        $bi = $datasource->get_block_instance();
        if ($bi && isset($bi->instance->id)) {
            $blockinstanceid = (int) $bi->instance->id;
        }
        $this->datasource->add_table(new details_area_table($blockinstanceid));
    }

    /**
     * If the layout supports field sorting.
     *
     * @return mixed
     */
    public function supports_sorting() {
        return false;
    }

    /**
     * If the layout supports options.
     */
    public function supports_download() {
        return false;
    }

    /**
     * Get the data source used as a data/configuration source for this layout.
     *
     * @return data_source_interface
     */
    public function get_data_source() {
        return $this->datasource;
    }

    /**
     * Get data strategy.
     *
     * @return data_strategy_interface
     */
    public function get_data_strategy() {
        return new standard_strategy();
    }

    /**
     * Modify objects before data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     */
    public function before_data() {
        // Propagate the block-level "details_custom_content" preference to the
        // smart_course_button and enrollment_options field attributes.
        $customcontentkey = $this->get_data_source()->get_preferences('details_custom_content');
        $hascustomcontent = (!empty($customcontentkey) && $customcontentkey !== 'none');

        $blockinstanceid = 0;
        $bi = $this->get_data_source()->get_block_instance();
        if ($bi && isset($bi->instance->id)) {
            $blockinstanceid = (int) $bi->instance->id;
        }

        // Calculate showdetailsarea flag from the details_area preference.
        $detailsarea = $this->get_data_source()->get_preferences('details_area');
        $showdetailsarea = !empty($detailsarea) && ($detailsarea !== 'disabled');

        $targetclasses = [
            'local_dash\\data_grid\\field\\attribute\\smart_course_button_attribute',
            'local_dash\\data_grid\\field\\attribute\\enrollment_options_attribute',
        ];

        // Propagate showdetailsarea to details button and link attributes.
        $detailsattributeclasses = [
            'block_dash\\local\\data_grid\\field\\attribute\\details_button_attribute',
            'block_dash\\local\\data_grid\\field\\attribute\\details_link_attribute',
        ];

        foreach ($this->get_data_source()->get_available_fields() as $field) {
            foreach ($field->get_attributes() as $attribute) {
                if (in_array(get_class($attribute), $targetclasses)) {
                    $attribute->set_option('has_details_custom_content', $hascustomcontent);
                    $attribute->set_option('blockinstanceid', $blockinstanceid);
                }
                if (in_array(get_class($attribute), $detailsattributeclasses)) {
                    $attribute->set_option('showdetailsarea', $showdetailsarea);
                }
            }
        }
    }

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {
        // Map details area fields for every layout.
        $this->map_details_area_data($datacollection);
    }

    /**
     * Map details area field preferences into template-friendly data variables on each row.
     * Also injects custom content (e.g. standard terms) when configured.
     *
     * Handles both flat data collections (rows at top level) and grouped collections
     * (sections -> rows, used by accordion layout).
     *
     * @param data_collection_interface $datacollection
     */
    protected function map_details_area_data(data_collection_interface $datacollection) {
        global $DB;

        $detailmapping = [
            'detailheader' => $this->get_data_source()->get_preferences('details_header'),
            'detailheading' => $this->get_data_source()->get_preferences('details_title'),
            'detailbody' => $this->get_data_source()->get_preferences('details_body_1'),
            'detailbody2' => $this->get_data_source()->get_preferences('details_body_2'),
            'detailbody3' => $this->get_data_source()->get_preferences('details_body_3'),
            'detailfooter' => $this->get_data_source()->get_preferences('details_footer_left'),
            'detailfooterright' => $this->get_data_source()->get_preferences('details_footer_right'),
        ];

        $customcontentkey = $this->get_data_source()->get_preferences('details_custom_content');
        $customcontent = '';
        if ($customcontentkey && $customcontentkey !== 'none') {
            if ($customcontentkey === 'standard_terms') {
                $customcontent = get_config('block_dash', 'standard_terms');
                $customcontent = $customcontent ? format_text($customcontent, FORMAT_HTML) : '';
            } else {
                if (block_dash_has_pro()) {
                    if ($layout = $DB->get_record('dashaddon_developer_layout', ['id' => $customcontentkey])) {
                        if (!empty($layout->mustache_template)) {
                            $customcontent = format_text($layout->mustache_template, FORMAT_HTML);
                        }
                    }
                }
            }
        }

        // Try flat structure first (standard_strategy: datacollection -> rows).
        $rows = $datacollection->get_child_collections('rows');
        if (!empty($rows)) {
            $this->apply_details_to_rows($rows, $detailmapping, $customcontent);
        }

        // Handle grouped structure (grouped_strategy: datacollection -> sections -> rows).
        $sections = $datacollection->get_child_collections('sections');
        if (!empty($sections)) {
            foreach ($sections as $section) {
                $sectionrows = $section->get_child_collections('rows');
                if (!empty($sectionrows)) {
                    $this->apply_details_to_rows($sectionrows, $detailmapping, $customcontent);
                }
            }
        }
    }

    /**
     * Apply details area field mapping and custom content to a set of row collections.
     *
     * @param data_collection_interface[] $rows Array of row data collections.
     * @param array $detailmapping Field mapping array.
     * @param string $customcontent Formatted custom content HTML (empty string if none).
     */
    private function apply_details_to_rows(array $rows, array $detailmapping, string $customcontent) {
        global $PAGE, $DB;
        foreach ($rows as $childcollection) {
            $this->map_data($detailmapping, $childcollection, false);
            $content = $customcontent;
            $customcontentkey = $this->get_data_source()->get_preferences('details_custom_content');
            if ($customcontentkey && $customcontentkey !== 'none' && $customcontentkey !== 'standard_terms') {
                if (block_dash_has_pro()) {
                    if ($layout = $DB->get_record('dashaddon_developer_layout', ['id' => $customcontentkey])) {
                        $renderer = $PAGE->get_renderer('block_dash');
                        $layoutobject = new \dashaddon_developer\layout\persistent_layout(
                            $this->get_data_source()
                        );
                        $layoutobject->set_custom_layout(
                            new \dashaddon_developer\model\custom_layout($layout->id)
                        );
                        $template = $layoutobject->get_mustache_template_name();
                        $rootcollection = new \block_dash\local\data_grid\data\data_collection();
                        $rootcollection->add_child_collection('rows', $childcollection);

                        $context = [
                            'data' => $rootcollection,
                        ];

                        $varsclass = '\dashaddon_developer\layout\vars';

                        if (class_exists($varsclass)) {
                            if (method_exists($varsclass, 'current_user_context')) {
                                $rowuser = null;

                                if (isset($childcollection['u_id']) && !empty($childcollection['u_id'])) {
                                    $rowuser = (object) ['id' => $childcollection['u_id']];
                                }

                                $context = array_merge($context, $varsclass::current_user_context($rowuser));
                            }

                            if (method_exists($varsclass, 'current_course_context')) {
                                $context = array_merge($context, $varsclass::current_course_context($childcollection));
                            }
                        }

                        $content = $renderer->render_from_template(
                            $template,
                            $context
                        );
                    }
                }
            }
            if ($content) {
                $childcollection->add_data(new field('customcontent', $content, false));
            }
        }
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
        global $OUTPUT;

        self::$currentgroupid = random_int(1, 10000);

        $filtercollection = $this->get_data_source()->get_filter_collection();

        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            if ($this->supports_field_visibility()) {
                $group = [];
                foreach ($this->get_data_source()->get_sorted_fields() as $availablefield) {
                    if ($availablefield->has_attribute(identifier_attribute::class)) {
                        continue;
                    }

                    if ($availablefield->has_attribute(context_attribute::class)) {
                        continue;
                    }

                    $fieldname = 'config_preferences[available_fields][' . $availablefield->get_alias() .
                        '][visible]';

                    $title = $availablefield->get_table()->get_title();

                    $icon = $OUTPUT->pix_icon(
                        'i/dragdrop',
                        get_string('dragitem', 'block_dash'),
                        'moodle',
                        ['class' => 'drag-handle']
                    );
                    $title = $icon . '<b>' . $title . '</b>: ' . $availablefield->get_title();

                    $totaratitle = block_dash_is_totara() ? $title : null;
                    $group[] = $mform->createElement('advcheckbox', $fieldname, $title, $totaratitle, [
                        'group' => self::$currentgroupid, // For legacy add_checkbox_controller().
                        'data-togglegroup' => 'group' . self::$currentgroupid, // For checkbox_toggleall.
                        'data-toggle' => 'slave', // For checkbox_toggleall.
                        'data-action' => 'toggle', // For checkbox_toggleall.
                    ]);
                    $mform->setType($fieldname, PARAM_BOOL);
                }
                $mform->addGroup(
                    $group,
                    'available_fields',
                    get_string('enabledfields', 'block_dash'),
                    [''],
                    false
                );

                $this->add_checkbox_toggleall(self::$currentgroupid, $form, $mform);

                self::$currentgroupid++;
            }
        }

        if ($this->supports_filtering()) {
            if ($form->get_tab() == preferences_form::TAB_FILTERS) {
                $mform->addElement('static', 'filterslabel', '', '<b>' . get_string('enabledfilters', 'block_dash') . '</b>');
                $filtercollection->build_settings_form($form, $mform, 'filter', 'config_preferences[filters][%s]');
            }
        }

        if ($form->get_tab() == preferences_form::TAB_CONDITIONS) {
            $mform->addElement('static', 'conditionslabel', '', '<b>' . get_string('enabledconditions', 'block_dash') . '</b>');
            $filtercollection->build_settings_form($form, $mform, 'condition', 'config_preferences[filters][%s]');
        }

        if (!$this->supports_filtering() && $form->get_tab() == preferences_form::TAB_FILTERS) {
            $mform->addElement('html', $OUTPUT->notification(get_string('layoutdoesnotsupportfiltering', 'block_dash'), 'warning'));
        }

        // Details area tab — available for all layouts.
        if ($form->get_tab() == preferences_form::TAB_DETAILS) {
            global $CFG;
            require_once($CFG->dirroot . '/blocks/dash/form/element-colorpicker.php');
            \MoodleQuickForm::registerElementType(
                'dashcolorpicker',
                $CFG->dirroot . '/blocks/dash/form/element-colorpicker.php',
                'moodlequickform_dashcolorpicker'
            );

            $noneoption = [null => get_string('none', 'block_dash')];

            $detailsareaoptions = [
                'disabled' => get_string('strdisabled', 'block_dash'),
                'expanding' => get_string('strexpanding', 'block_dash'),
                'floating'  => get_string('strfloating', 'block_dash'),
                'modal'     => get_string('strmodal', 'block_dash'),
            ];
            $mform->addElement(
                'select',
                'config_preferences[details_area]',
                get_string('details_area', 'block_dash'),
                $detailsareaoptions
            );
            $mform->setType('config_preferences[details_area]', PARAM_TEXT);
            $mform->setDefault('config_preferences[details_area]', 'disabled');
            $mform->addHelpButton('config_preferences[details_area]', 'details_area', 'block_dash');

            $detailsareasizeoptions = [
                'like_item'   => get_string('like_item', 'block_dash'),
                'fit_content' => get_string('fit_content', 'block_dash'),
            ];
            $mform->addElement(
                'select',
                'config_preferences[details_area_size]',
                get_string('details_area_size', 'block_dash'),
                $detailsareasizeoptions
            );
            $mform->setType('config_preferences[details_area_size]', PARAM_TEXT);
            $mform->setDefault('config_preferences[details_area_size]', 'like_item');
            $mform->addHelpButton('config_preferences[details_area_size]', 'details_area_size', 'block_dash');
            $mform->hideIf('config_preferences[details_area_size]', 'config_preferences[details_area]', 'eq', 'disabled');
            $mform->hideIf('config_preferences[details_area_size]', 'config_preferences[details_area]', 'eq', 'modal');

            $availablefields = $this->get_data_source()->get_available_fields();
            $alloptions = array_merge($noneoption, field_definition_factory::get_field_definition_options($availablefields));

            $mform->addElement(
                'select',
                'config_preferences[details_header]',
                get_string('details_header', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_header]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_header]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_title]',
                get_string('details_title', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_title]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_title]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_body_1]',
                get_string('details_body_1', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_body_1]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_body_1]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_body_2]',
                get_string('details_body_2', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_body_2]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_body_2]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_body_3]',
                get_string('details_body_3', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_body_3]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_body_3]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_footer_left]',
                get_string('details_footer_left', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_footer_left]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_footer_left]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'select',
                'config_preferences[details_footer_right]',
                get_string('details_footer_right', 'block_dash'),
                $alloptions
            );
            $mform->setType('config_preferences[details_footer_right]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_footer_right]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'dashcolorpicker',
                'config_preferences[details_bg_color]',
                get_string('details_bg_color', 'block_dash')
            );
            $mform->setType('config_preferences[details_bg_color]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_bg_color]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'dashcolorpicker',
                'config_preferences[details_text_color]',
                get_string('details_text_color', 'block_dash')
            );
            $mform->setType('config_preferences[details_text_color]', PARAM_TEXT);
            $mform->hideIf('config_preferences[details_text_color]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement('html', '<hr>');

            $customcontentoptions = [
                'none'           => get_string('standard_terms_none', 'block_dash'),
                'standard_terms' => get_string('standard_terms', 'block_dash'),
            ];
            $customcontentoptions = $this->get_custom_content_options($customcontentoptions);

            $mform->addElement(
                'select',
                'config_preferences[details_custom_content]',
                get_string('details_custom_content', 'block_dash'),
                $customcontentoptions
            );
            $mform->setType('config_preferences[details_custom_content]', PARAM_TEXT);
            $mform->setDefault('config_preferences[details_custom_content]', 'none');
            $mform->hideIf('config_preferences[details_custom_content]', 'config_preferences[details_area]', 'eq', 'disabled');

            $mform->addElement(
                'text',
                'config_preferences[details_custom_content_height]',
                get_string('details_custom_content_height', 'block_dash')
            );
            $mform->setType('config_preferences[details_custom_content_height]', PARAM_TEXT);
            $mform->addHelpButton(
                'config_preferences[details_custom_content_height]',
                'details_custom_content_height',
                'block_dash'
            );
            $mform->hideIf(
                'config_preferences[details_custom_content_height]',
                'config_preferences[details_area]',
                'eq',
                'disabled'
            );
            $mform->hideIf(
                'config_preferences[details_custom_content_height]',
                'config_preferences[details_custom_content]',
                'eq',
                'none'
            );
        }
    }

    /**
     * Add button to select/deselect all checkboxes in group.
     *
     * @param string $uniqueid
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     */
    private function add_checkbox_toggleall($uniqueid, \moodleform $form, \MoodleQuickForm $mform) {
        global $OUTPUT;

        if (class_exists('\core\output\checkbox_toggleall')) {
            $masterbutton = new \core\output\checkbox_toggleall('group' . $uniqueid, true, [], true);

            // Then you can export for template.
            $mform->addElement('static', 'toggleall' . $uniqueid, '', $OUTPUT->render($masterbutton));
        } else {
            // Moodle 3.7 and earlier support.
            $form->add_checkbox_controller($uniqueid);
        }
    }

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences) {
        // Details area boolean flags – available for ALL layouts.
        $preferences['showdetailsarea'] = isset($preferences['details_area']) &&
            ($preferences['details_area'] != 'disabled') ? true : false;

        $preferences['detailareaexpand'] = isset($preferences['details_area']) &&
            ($preferences['details_area'] == 'expanding') ? true : false;
        $preferences['detailareafloating'] = isset($preferences['details_area']) &&
            ($preferences['details_area'] == 'floating') ? true : false;
        $preferences['detailareamodal'] = isset($preferences['details_area']) &&
            ($preferences['details_area'] == 'modal') ? true : false;
        // String mode value for JS consumption.
        $preferences['details_area_mode'] = $preferences['details_area'] ?? 'disabled';
        if ($preferences['showdetailsarea']) {
            if (isset($preferences['details_area_size']) && ($preferences['details_area_size'] == 'fit_content')) {
                $preferences['fitcontentdetailsarea'] = true;
            } else {
                $preferences['fitcarddetailsarea'] = true;
            }
        }
        // Normalize custom content height: append 'px' if the value is a plain number.
        if (!empty($preferences['details_custom_content_height'])) {
            $height = trim($preferences['details_custom_content_height']);
            if (is_numeric($height)) {
                $preferences['details_custom_content_height'] = $height . 'px';
            }
        }
        return $preferences;
    }

    /**
     * Returns options for the details area custom content dropdown.
     * Override in a subclass to inject additional options.
     *
     * @param array $options Current options array (key => display label).
     * @return array Modified options array.
     */
    protected function get_custom_content_options(array $options): array {
        global $DB;

        if (block_dash_has_pro() && $DB->get_manager()->table_exists('dashaddon_developer_layout')) {
            $layouts = $DB->get_records('dashaddon_developer_layout');
            foreach ($layouts as $layout) {
                if (in_array($layout->type, ['detailsarea', 'both'])) {
                    $options[$layout->id] = $layout->name;
                }
            }
        }
        return $options;
    }

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(\renderer_base $output) {
        global $OUTPUT, $PAGE, $CFG;

        $config = $this->get_data_source()->get_block_instance()->config;
        $noresulttxt = \html_writer::tag('p', get_string('noresults'), ['class' => 'text-muted']);

        $templatedata = [
            'error' => '',
            'paginator' => '',
            'data' => null,
            'uniqueid' => uniqid(),
            'is_totara' => block_dash_is_totara(),
            'bootstrap3' => get_config('block_dash', 'bootstrap_version') == 3,
            'bootstrap4' => get_config('block_dash', 'bootstrap_version') == 4,
            'noresult' => (isset($config->emptystate))
                ? format_text($config->emptystate['text'], FORMAT_HTML, ['noclean' => true]) : $noresulttxt,
            'datatoggle' => ($CFG->branch >= 500) ? 'data-bs-toggle' : 'data-toggle',
            'datatarget' => ($CFG->branch >= 500) ? 'data-bs-target' : 'data-target',
            'dataparent' => ($CFG->branch >= 500) ? 'data-bs-parent' : 'data-parent',
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

            if (
                !$this->get_data_source()->supports_ajax_pagination() &&
                $this->get_data_source()->get_paginator()->get_page_count() > 1
            ) {
                $templatedata['paginator'] = $OUTPUT->render_from_template(paginator::TEMPLATE, $this->get_data_source()
                    ->get_paginator()
                    ->export_for_template($OUTPUT));
            } else {
                $templatedata['paginator'] = html_writer::tag('div', '', ['class' => 'ajax-pagination']);
            }
        }

        $layout = isset($config->preferences['layout']) ? $config->preferences['layout'] : '';
        $formhtml = $this->get_data_source()->get_filter_collection()->create_form_elements('', $layout);
        // Get downloads butttons.
        $downloadcontent = '';
        if ($this->supports_download() && $this->get_data_source()->get_preferences('exportdata')) {
            $downloadoptions = [];
            $options = [];
            $downloadlist = '';
            $options['sesskey'] = sesskey();
            $options["download"] = "csv";
            $button = $OUTPUT->single_button(new moodle_url($PAGE->url, $options), get_string("downloadcsv", 'block_dash'), 'get');
            $downloadoptions[] = html_writer::tag('li', $button, ['class' => 'reportoption list-inline-item']);

            $options["download"] = "excel";
            $button = $OUTPUT->single_button(new moodle_url($PAGE->url, $options), get_string("downloadexcel"), 'get');
            $downloadoptions[] = html_writer::tag('li', $button, ['class' => 'reportoption list-inline-item']);

            $downloadlist .= html_writer::tag('ul', implode('', $downloadoptions), ['class' => 'list-inline inline']);
            $downloadlist .= html_writer::tag('div', '', ['class' => 'clearfloat']);
            $downloadcontent .= html_writer::tag('div', $downloadlist, ['class' => 'downloadreport mt-1']);
        }

        if (!is_null($templatedata['data'])) {
            $templatedata = array_merge($templatedata, [
                'filter_form_html' => $formhtml,
                'downloadcontent' => $downloadcontent,
                'supports_filtering' => $this->supports_filtering(),
                'supports_download' => $this->supports_download(),
                'supports_pagination' => $this->supports_pagination(),
                'preferences' => $this->process_preferences($this->get_data_source()->get_all_preferences()),
            ]);
        }
        // Values for placeholders.
        if (
            class_exists('\dashaddon_developer\layout\vars') &&
            method_exists('\dashaddon_developer\layout\vars', 'current_user_context')
        ) {
            $templatedata = array_merge($templatedata, \dashaddon_developer\layout\vars::current_user_context());
        }
        return $templatedata;
    }

    /**
     * Map data.
     *
     * @param array $mapping
     * @param data_collection_interface $datacollection
     * @param bool $visible Whether the mapped fields should be visible in the data iteration (e.g. table columns).
     * @return data_collection_interface
     */
    protected function map_data($mapping, data_collection_interface $datacollection, bool $visible = true) {
        foreach ($mapping as $newname => $fieldname) {
            if ($fieldname && !is_array($fieldname) && isset($datacollection[$fieldname])) {
                $datacollection->add_data(new field($newname, $datacollection[$fieldname], $visible));
            } else if ($fieldname && is_array($fieldname)) {
                $value = array_map(function ($field) use ($datacollection) {
                    return $datacollection[$field];
                }, $fieldname);
                $datacollection->add_data(new field($newname, implode(" ", $value), $visible));
            }
        }
        return $datacollection;
    }

    /**
     * Returns supported icons.
     *
     * @return array
     */
    protected function get_icon_list() {
        global $PAGE;

        $icons = [];

        if (isset($PAGE->theme->iconsystem)) {
            if ($iconsystem = \core\output\icon_system::instance($PAGE->theme->iconsystem)) {
                if ($iconsystem instanceof \core\output\icon_system_fontawesome) {
                    foreach ($iconsystem->get_icon_name_map() as $pixname => $faname) {
                        $icons[$faname] = $pixname;
                    }
                }
            }
        } else if (block_dash_is_totara()) {
            foreach (\core\output\flex_icon_helper::get_icons($PAGE->theme->name) as $iconkey => $icon) {
                $icons[$iconkey] = $iconkey;
            }
        }

        return $icons;
    }
}
