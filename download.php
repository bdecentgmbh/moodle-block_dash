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
 * Field definitions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require($CFG->dirroot . '/blocks/dash/locallib.php');

use block_dash\local\block_builder;

$download = optional_param('download', 'csv', PARAM_TEXT);
$instanceid = required_param('block_instance_id', PARAM_INT);
$filterformdata = optional_param('filter_form_data', '', PARAM_TEXT);
$currentpage = optional_param('page', 0, PARAM_INT);
$sortfield = optional_param('sort_field', '', PARAM_TEXT);
$sortdir = optional_param('sort_direction', '', PARAM_TEXT);

$binstance = $DB->get_record('block_instances', ['id' => $instanceid, 'blockname' => 'dash'], '*', MUST_EXIST);
$block = block_instance('dash', $binstance);

// Whoever downloads a block's data must be able to see the block where it is.
[$context, $course, $cm] = get_context_info_array($block->context->id);
require_login($course, false, $cm);
require_capability('moodle/block:view', $block->context);
if (!$block->verify_access_restrictions()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('enabledownload', 'block_dash'));
}

$PAGE->set_context($block->context);
$PAGE->set_url('/blocks/dash/download.php', ['block_instance_id' => $instanceid]);

$renderer = $PAGE->get_renderer('block_dash');
if ($sortfield) {
    $block->set_sort($sortfield, $sortdir);
}

$bbdownload = block_builder::create($block);
if (!$bbdownload->get_configuration()->get_data_source()->get_preferences('exportdata')) {
    return false;
}

foreach (json_decode($filterformdata, true) as $filter) {
    $bbdownload->get_configuration()
        ->get_data_source()
        ->get_filter_collection()
        ->apply_filter($filter['name'], $filter['value']);
}

$bbdownloadsource = $bbdownload->get_configuration()->get_data_source();
$bbdownloadsource->set_data_pagination(); // Set before data method to apply filters first.
// All rows, not only the current page, but no more than the block shows at most.
$bbdownloadsource->get_query()->limitfrom(0)->limitnum((int) $bbdownloadsource->get_preferences('maxlimit'));

// Fetch the list of fields to show. List the fields as headers and columns.
$headers = [];
$columns = [];
$fields = $bbdownloadsource->get_available_fields();
foreach ($fields as $key => $field) {
    if (is_null($field->get_select()) || !$field->get_visibility()) {
        continue;
    }
    $headers[] = $field->get_title();
    $columns[] = $key;
}

// Generate the filename.
$file = $bbdownload->get_configuration()->get_data_source()->get_name();
$filename = $file . "_" . get_string('strdatasource', 'block_dash');

$downloadtable = new \block_dash\form\block_dash_download_table('dash_downloadtable');

// Define the columns and headers.
$downloadtable->define_columns($columns);
$downloadtable->define_headers($headers);
$downloadtable->define_baseurl(new moodle_url('/blocks/dash/download.php', ['block_instance_id' => $instanceid]));

// Set the datasource for the table.
$downloadtable->set_datasource($bbdownloadsource);

$downloadtable->is_downloading($download, $filename);

[$sql, $params] = $bbdownloadsource->get_query()->get_sql_and_params();
$downloadtable->set_data($sql, $params);

$downloadtable->out(0, false);
