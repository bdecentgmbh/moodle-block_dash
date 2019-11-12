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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_dash_get_database_schema_structure' => [
        'classname'     => 'block_dash\external',
        'classpath'     => '',
        'methodname'    => 'get_database_schema_structure',
        'description'   => 'Get database schema structure info, tables and fields.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'block_dash_get_field_edit_row' => [
        'classname'     => 'block_dash\external',
        'classpath'     => '',
        'methodname'    => 'get_field_edit_row',
        'description'   => 'Get HTML for new field edit row.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
