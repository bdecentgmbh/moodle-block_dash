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
 * Download a Dash block's setup as a file.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\data_source\data_source_factory;
use block_dash\local\porter;

require_once(__DIR__ . '/../../config.php');

$blockid = required_param('id', PARAM_INT);

$instance = $DB->get_record('block_instances', ['id' => $blockid, 'blockname' => 'dash'], '*', MUST_EXIST);
$context = context_block::instance($blockid);
[$context, $course, $cm] = get_context_info_array($context->id);

require_login($course, false, $cm);
require_sesskey();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/dash/export.php', ['id' => $blockid]));

$block = block_instance('dash', $instance);
$datasource = $block->config->data_source_idnumber ?? '';

if (!porter::can_edit_block($block) || !$datasource || !data_source_factory::can_manage($datasource, $context)) {
    throw new required_capability_exception($context, 'moodle/block:edit', 'nopermissions', '');
}

$json = json_encode(porter::export($block), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
send_file($json, porter::get_filename($block), 0, 0, true, true, 'application/json');
