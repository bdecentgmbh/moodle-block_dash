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
 * List of templates.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\template\table\template_table;

require(__DIR__.'/../../config.php');
require_once("$CFG->libdir/adminlib.php");

global $USER;

$context = context_system::instance();

admin_externalpage_setup('blockdashmanagetemplates');

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/dash/templates.php'));
$PAGE->set_title(get_string('managetemplates', 'block_dash'));
$PAGE->set_heading(get_string('managetemplates', 'block_dash'));
$PAGE->set_button($OUTPUT->single_button(new moodle_url('/blocks/dash/template.php', ['action' => 'create']),
    get_string('createtemplate', 'block_dash')));
$PAGE->navbar->add(get_string('managetemplates', 'block_dash'));

require_login();
require_capability('block/dash:managetemplates', $context);

$table = new template_table('templates');
$table->define_baseurl($PAGE->url);

echo $OUTPUT->header();
$table->out(25, false);
echo $OUTPUT->footer();
