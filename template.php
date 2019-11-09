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

use block_dash\template\form\template_form;

require_once(__DIR__.'/../../config.php');
require_once("$CFG->libdir/adminlib.php");

global $PAGE, $DB;

$action = required_param('action', PARAM_TEXT);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/dash/template.php', ['action' => $action]));
$PAGE->navbar->add(get_string('managetemplates', 'block_dash'),
    new moodle_url('/blocks/dash/templates.php'));


require_login();
require_capability('block/dash:managetemplates', $context);

switch ($action) {
    case 'create':
        $PAGE->set_title(get_string('createtemplate', 'block_dash'));
        $PAGE->set_heading(get_string('createtemplate', 'block_dash'));
        $PAGE->navbar->add(get_string('createtemplate', 'block_dash'));

        $PAGE->requires->js_call_amd('block_dash/template_form');
        $PAGE->requires->css('/blocks/dash/codemirror.css');

        $form = new template_form($PAGE->url);

        if ($data = $form->get_data()) {
            $DB->insert_record('dash_template', $data);

            \core\notification::success(get_string('templatecreated', 'block_dash', $data));
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/blocks/dash/templates.php'));
        }

        break;

    case 'edit':
        $PAGE->set_title(get_string('edittemplate', 'block_dash'));
        $PAGE->set_heading(get_string('edittemplate', 'block_dash'));
        $PAGE->navbar->add(get_string('edittemplate', 'block_dash'));

        $PAGE->requires->js_call_amd('block_dash/template_form');
        $PAGE->requires->css('/blocks/dash/codemirror.css');

        $id = required_param('id', PARAM_INT);

        $template = $DB->get_record('dash_template', ['id' => $id], '*', MUST_EXIST);

        $form = new template_form($PAGE->url);

        if ($data = $form->get_data()) {
            $data->available_field_definitions = json_encode($data->available_field_definitions);
            $DB->update_record('dash_template', $data);

            \core\notification::success(get_string('templateedited', 'block_dash', $data));
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/blocks/dash/templates.php'));
        } else {
            $template->available_field_definitions = json_decode($template->available_field_definitions, true);

            $form->set_data($template);
        }

        break;
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
