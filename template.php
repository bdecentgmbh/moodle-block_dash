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
 * Version details
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\output\renderer;
use block_dash\template\form\template_general_form;
use block_dash\template\custom_template;

require_once(__DIR__.'/../../config.php');
require_once("$CFG->libdir/adminlib.php");

global $PAGE, $DB;

$action = required_param('action', PARAM_TEXT);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/dash/template.php', ['action' => $action]));
$PAGE->navbar->add(get_string('managetemplates', 'block_dash'),
    new moodle_url('/blocks/dash/templates.php'));

/** @var renderer $renderer */
$renderer = $PAGE->get_renderer('block_dash');

require_login();
require_capability('block/dash:managetemplates', $context);

switch ($action) {
    case 'create':
        $PAGE->set_title(get_string('createtemplate', 'block_dash'));
        $PAGE->set_heading(get_string('createtemplate', 'block_dash'));
        $PAGE->navbar->add(get_string('createtemplate', 'block_dash'));

        $PAGE->requires->js_call_amd('block_dash/template_form');
        $PAGE->requires->css('/blocks/dash/codemirror.css');
        $PAGE->requires->css('/blocks/dash/codemirror-show-hint.css');

        $form = new template_general_form($PAGE->url);

        if ($data = $form->get_data()) {
            $data->available_field_definitions = json_encode($data->available_field_definitions);
            $DB->insert_record('dash_template', $data);

            \core\notification::success(get_string('templatecreated', 'block_dash', $data));
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/blocks/dash/templates.php'));
        }

        echo $OUTPUT->header();

        break;

    case 'edit':
        $PAGE->set_title(get_string('edittemplate', 'block_dash'));
        $PAGE->set_heading(get_string('edittemplate', 'block_dash'));
        $PAGE->navbar->add(get_string('edittemplate', 'block_dash'));

        $PAGE->requires->js_call_amd('block_dash/template_form');
        $PAGE->requires->css('/blocks/dash/codemirror.css');
        $PAGE->requires->css('/blocks/dash/codemirror-show-hint.css');

        $id = required_param('id', PARAM_INT);
        $section = optional_param('section', 'general', PARAM_TEXT);
        $url = clone $PAGE->url;
        $url->params(['id' => $id, 'section' => $section]);

        $templaterecord = $DB->get_record('dash_template', ['id' => $id], '*', MUST_EXIST);
        $template = custom_template::create($templaterecord, context_system::instance());

        $formclass = sprintf('\block_dash\template\form\template_%s_form', $section);
        $form = new $formclass($url, ['template' => $template]);

        if ($data = $form->get_data()) {
            if (isset($_POST['available_field_definitions'])) {
                $data->available_field_definitions = json_encode($_POST['available_field_definitions']);
            }
            $DB->update_record('dash_template', $data);

            \core\notification::success(get_string('templateedited', 'block_dash', $template));
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else {
            $form->set_data($templaterecord);
        }

        echo $OUTPUT->header();
        echo $renderer->render_editing_tabs($id, $section);

        break;
}

$form->display();
echo $OUTPUT->footer();
