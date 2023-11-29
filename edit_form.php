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
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_dash\local\data_source\data_source_factory;

/**
 * Form for editing Dash block instances.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_dash_edit_form extends block_edit_form {

    /**
     * Add form fields.
     *
     * @param MoodleQuickForm $mform
     * @throws coding_exception
     */
    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'dashconfigheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_dash'));
        $mform->setType('config_title', PARAM_TEXT);

        $this->add_datasource_group($mform, $this->block->config);

        $mform->addElement('header', 'headerfooter', get_string('headerfooter', 'block_dash'));

        $mform->addElement('editor', 'config_header_content', get_string('headercontent', 'block_dash'));
        $mform->setType('config_header_content', PARAM_RAW);
        $mform->addHelpButton('config_header_content', 'headercontent', 'block_dash');

        $mform->addElement('editor', 'config_footer_content', get_string('footercontent', 'block_dash'));
        $mform->setType('config_footer_content', PARAM_RAW);
        $mform->addHelpButton('config_footer_content', 'footercontent', 'block_dash');

        $mform->addElement('header', 'apperance', get_string('appearance'));

        $mform->addElement('select', 'config_width', get_string('blockwidth', 'block_dash'), [
            100 => '100',
            50 => '1/2',
            33 => '1/3',
            66 => '2/3',
            25 => '1/4',
            20 => '1/5',
            16 => '1/6'
        ]);
        $mform->setType('config_width', PARAM_INT);
        $mform->setDefault('config_width', 100);

        $mform->addElement('select', 'config_hide_when_empty', get_string('hidewhenempty', 'block_dash'), [
            0 => get_string('no'),
            1 => get_string('yes')
        ]);
        $mform->setType('config_hide_When_empty', PARAM_BOOL);

        $mform->addElement('text', 'config_css_class', get_string('cssclass', 'block_dash'));
        $mform->setType('config_css_class', PARAM_TEXT);

        $mform->addElement('filemanager', 'config_backgroundimage', get_string('backgroundimage', 'block_dash'), null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['image'], 'return_types' => FILE_INTERNAL | FILE_EXTERNAL]);
        $mform->addHelpButton('config_backgroundimage', 'backgroundimage', 'block_dash');

        $mform->addElement('text', 'config_backgroundgradient', get_string('backgroundgradient', 'block_dash'),
            ['placeholder' => 'linear-gradient(#e66465, #9198e5)']);
        $mform->setType('config_backgroundgradient', PARAM_TEXT);
        $mform->addHelpButton('config_backgroundgradient', 'backgroundgradient', 'block_dash');

        $mform->addElement('text', 'config_headerfootercolor', get_string('fontcolor', 'block_dash'));
        $mform->setType('config_headerfootercolor', PARAM_TEXT);
        $mform->addHelpButton('config_headerfootercolor', 'fontcolor', 'block_dash');

        $mform->addElement('text', 'config_css[border]', get_string('border', 'block_dash'));
        $mform->setType('config_css[border]', PARAM_TEXT);
        $mform->addHelpButton('config_css[border]', 'border', 'block_dash');

        $mform->addElement('text', 'config_css[min-height]', get_string('minheight', 'block_dash'));
        $mform->setType('config_css[min-height]', PARAM_TEXT);
        $mform->addHelpButton('config_css[min-height]', 'minheight', 'block_dash');

        $mform->addElement('header', 'emptystateheading', get_string('emptystateheading', 'block_dash'));

        $mform->addElement('editor', 'config_emptystate', get_string('content'), ['rows' => 5]);
        $mform->setType('config_emptystate', PARAM_CLEANHTML);

    }

    /**
     * Add available data source groups.
     *
     * @param moodleform $mform
     * @param stdclass $config
     * @return void
     */
    public function add_datasource_group(&$mform, $config) {
        global $OUTPUT;

        $label[] = $mform->createElement('html', html_writer::start_div('datasource-content heading'));
        $label[] = $mform->createElement('html', html_writer::end_div());

        $mform->addGroup($label, 'datasources_label', get_string('choosefeature', 'block_dash'), array(' '), false);
        $mform->setType('datasources_label', PARAM_TEXT);

        if (!isset($config->data_source_idnumber)) {

            self::dash_features_list($mform, $this->block->context, $this->page);

        } else {
            if ($ds = data_source_factory::build_data_source($config->data_source_idnumber,
                $this->block->context)) {
                $label = $ds->get_name();
            } else {
                $label = get_string('datasourcemissing', 'block_dash');
            }
            $datalabel = ($ds->is_widget()
            ? get_string('widget', 'block_dash') : get_string('datasource', 'block_dash'));

            $mform->addElement('static', 'data_source_label', $datalabel.' : ', $label);
        }
    }

    public static function dash_features_list(&$mform, $context, $page) {
        global $OUTPUT;
        // Group of datasources.
        if (has_capability('block/dash:managedatasource', $context)) {
            $datasources = data_source_factory::get_data_source_form_options();

            // Description of the datasources.
            $group[] = $mform->createElement('html',
                html_writer::tag('p', get_string('datasourcedesc', 'block_dash'), ['class' => 'dash-source-desc']));

            $group[] = $mform->createElement('html', html_writer::start_div('datasource-content'));
            foreach ($datasources as $id => $source) {
                $group[] = $mform->createElement('html', html_writer::start_div('datasource-item'));
                $group[] = $mform->createElement('radio', 'config_data_source_idnumber', '', $source['name'], $id);
                if ($help = $source['help']) {
                    $helpcontent = $OUTPUT->help_icon($help['name'], $help['component'], $help['name']);
                    $group[] = $mform->createElement('html', $helpcontent);
                }
                $group[] = $mform->createElement('html', html_writer::end_div());
            }
            $group[] = $mform->createElement('html', html_writer::end_div());
            $mform->addGroup($group, 'datasources', get_string('buildown', 'block_dash'), array(' '), false);
            $mform->setType('datasources', PARAM_TEXT);
            $mform->addHelpButton('datasources', 'buildown', 'block_dash');
        }

        // Widgets data source.
        if (has_capability('block/dash:managewidget', $context)) {
            $widgetlist = data_source_factory::get_data_source_form_options('widget');
            $widgets[] = $mform->createElement('html',
                html_writer::tag('p', get_string('widgetsdesc', 'block_dash'), ['class' => 'dash-source-desc']));
            $widgets[] = $mform->createElement('html', html_writer::start_div('datasource-content'));

            foreach ($widgetlist as $id => $source) {
                $widgets[] = $mform->createElement('html', html_writer::start_div('datasource-item'));
                $widgets[] = $mform->createElement('radio', 'config_data_source_idnumber', '', $source['name'], $id);
                if ($source['help']) {
                    $widgets[] = $mform->createElement('html', $OUTPUT->help_icon($source['help'], 'block_dash', $source['help']));
                }
                $widgets[] = $mform->createElement('html', html_writer::end_div());
            }
            $widgets[] = $mform->createElement('html', html_writer::end_div());
            $mform->addGroup($widgets, 'widgets', get_string('readymatewidgets', 'block_dash'), array(' '), false);
            $mform->setType('widgets', PARAM_TEXT);
            $mform->addHelpButton('widgets', 'readymatewidgets', 'block_dash');
        }

        // Content layout.
        $customfeatures = data_source_factory::get_data_source_form_options('custom');
        if ($customfeatures) {
            foreach ($customfeatures as $id => $source) {
                if ($id::has_capbility($context)) {
                    $id::get_features_config($mform, $source);
                    $showcustom = true;
                }
            }
            if (isset($showcustom)) {

                $page->requires->js_amd_inline('require(["jquery"], function($) {
                        $("body").on("change", "[data-target=\"subsource-config\"] [type=radio]", function(e) {
                            var subConfig;
                            if (subConfig = e.target.closest("[data-target=\"subsource-config\"]")) {
                                if (subConfig.parentNode !== null) {
                                    var dataSource = subConfig.parentNode.querySelector("[name=\"config_data_source_idnumber\"]");
                                    dataSource.click(); // = true;
                                }
                            }
                        });
                    })'
                );
            }
        }
    }

    /**
     * Display the configuration form when block is being added to the page
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return false;
    }
}

/**
 * Dash features form to configure the data source or widget.
 */
class block_dash_featuresform extends \moodleform {

    /**
     * Defined the form fields for the datasource selector list.
     *
     * @return void
     */
    public function definition() {
        // @codingStandardsIgnoreStart
        global $PAGE;
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        // @codingStandardsIgnoreEnd

        $mform = $this->_form;

        $mform->updateAttributes(['class' => 'form-inline']);
        $mform->updateAttributes(['id' => 'dash-configuration']);

        $block = $this->_customdata['block'] ?? '';
        // @codingStandardsIgnoreStart
        // Ignore the phplint due to block class not allowed to include the PAGE global variable.
        block_dash_edit_form::dash_features_list($mform, $block, $PAGE);
        // @codingStandardsIgnoreEnd
    }
}
