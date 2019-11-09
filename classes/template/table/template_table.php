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

namespace block_dash\template\table;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table that lists templates.
 *
 * @package videotimeplugin_pro
 */
class template_table extends \table_sql
{
    /**
     * sessions_report_table constructor.
     * @param $cm_id
     * @throws \coding_exception
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('name');
        $headers[] = get_string('actions');
        $columns[] = 'name';
        $columns[] = 'actions';

        $this->define_columns($columns);
        $this->define_headers($headers);

        //$this->no_sorting('');

        // Set help icons.
        $this->define_help_for_headers([
            //'1' => new \help_icon('views', 'videotime'),
        ]);
    }

    /**
     * Actions for tags.
     *
     * @param \stdClass $data
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($data) {
        global $OUTPUT;

        return $OUTPUT->single_button(
                new \moodle_url('/blocks/dash/template.php', ['action' => 'edit', 'id' => $data->id]),
                get_string('edit', 'block_dash'), 'get') .
            $OUTPUT->single_button(
                new \moodle_url('/blocks/dash/template.php', ['action' => 'delete', 'id' => $data->id]),
                get_string('delete', 'block_dash'), 'get');
    }

    /**
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    function query_db($pagesize, $useinitialsbar = true)
    {
        global $DB;

        list($wsql, $params) = $this->get_sql_where();

        $sql = 'SELECT * FROM {dash_template} dt ' . $wsql;

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sql = $sql . ' ORDER BY ' . $sort;
        }

        if ($pagesize != -1) {
            $count_sql = 'SELECT COUNT(DISTINCT dt.id) FROM {dash_template} dt ' . $wsql;
            $total = $DB->count_records_sql($count_sql, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        $this->rawdata = $DB->get_recordset_sql($sql, $params, $this->get_page_start(), $this->get_page_size());
    }
}
