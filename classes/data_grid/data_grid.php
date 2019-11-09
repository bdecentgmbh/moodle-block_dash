<?php
// This file is part of The Bootstrap Moodle theme
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

namespace block_dash\data_grid;

use block_dash\data_grid\data\data_collection;
use block_dash\data_grid\data\data_collection_interface;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection_interface;
use block_dash\data_grid\filter\filter_interface;
use block_dash\data_grid\form\data_grid_filter_form;

/**
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package block_dash
 */
abstract class data_grid implements data_grid_interface, \JsonSerializable
{
    #region Properties

    /**
     * @var bool If the data grid definition has been built yet (fields, filters, etc)
     */
    private $initialized = false;

    /**
     * @var \context
     */
    private $context;

    /**
     * @var field_definition_interface[] All fields in this data grid. Key by field name.
     */
    private $field_definitions = [];

    /**
     * @var filter_collection_interface
     */
    private $filter_collection;

    /**
     * @var string
     */
    private $url = null;

    /**
     * @var \stdClass $user User displaying data (logged in user).
     */
    private $user;

    /**
     * @var data_grid_filter_form
     */
    private $form;

    /**
     * @var int Store record count so we don't have to query it multiple times.
     */
    private $record_count = null;

    /**
     * @var bool Disable any kind of pagination and return full data set. Useful for downloading all data.
     */
    private $disable_pagination = false;

    /**
     * @var paginator
     */
    private $paginator;

    /**
     * @var data_collection_interface
     */
    private $data_collection;

    #endregion

    /**
     * @param filter_collection_interface $filter_collection
     * @param \context $context
     * @param \stdClass $user User displaying data (logged in user).
     * @throws \coding_exception
     */
    public function __construct(filter_collection_interface $filter_collection,
                                \context $context, \stdClass $user = null)
    {
        global $USER;

        if (is_null($user)) {
            $this->user = $USER;
        }

        $this->context = $context;

        $this->filter_collection = $filter_collection;

        $this->paginator = new paginator(paginator::PER_PAGE_DEFAULT, function () {
            return $this->get_records_count();
        });
    }

    /**
     * Return the template identifier for this component.
     *
     * @return string
     */
    public function get_template()
    {
        return 'block_dash/data_grid';
    }

    /**
     * @return \context
     */
    public function get_context()
    {
        return $this->context;
    }

    #region Field methods

    /**
     * Get field definition by name. Returns false if not found.
     *
     * @param $name
     * @return bool|field_definition_interface
     */
    public function get_field_definition($name)
    {
        // Field definitions are keyed by name.
        if (isset($this->field_definitions[$name])) {
            return $this->field_definitions[$name];
        }

        return false;
    }

    /**
     * Get all field definitions in this data grid.
     *
     * @return field_definition_interface[]
     */
    public function get_field_definitions()
    {
        return array_values($this->field_definitions);
    }

    /**
     * Sets field definitions on data grid.
     *
     * @param field_definition_interface[] $field_definitions
     * @throws \moodle_exception
     */
    public function set_field_definitions($field_definitions)
    {
        // We don't want field definitions to be set multiple times, it should be done during init only.
        if ($this->has_any_field_definitions()) {
            throw new \coding_exception('Setting field definitions multiple times is not allowed.');
        }

        foreach ($field_definitions as $field_definition) {
            if (!$field_definition instanceof field_definition_interface) {
                throw new \moodle_exception('Field definition is of wrong type.');
            }

            $this->add_field_definition($field_definition);
        }
    }

    /**
     * Add a single field definition to the report.
     *
     * @param field_definition_interface $field_definition
     * @throws \moodle_exception
     */
    public function add_field_definition(field_definition_interface $field_definition)
    {
        $this->field_definitions[$field_definition->get_name()] = $field_definition;
    }

    /**
     * Check if grid has any field definitions set.
     *
     * @return bool
     */
    public function has_any_field_definitions()
    {
        return count($this->field_definitions) > 0;
    }

    /**
     * Check if report has a certain field
     *
     * @param $name
     * @return bool
     */
    public function has_field($name)
    {
        return !empty($this->get_field_definition($name));
    }

    #endregion

    #region Initializing methods

    /**
     * Initialize data grid.
     */
    public function init()
    {
        if ($this->get_filter_collection()->get_filters()) {
            foreach ($this->get_field_definitions() as $field_definition) {
                $this->filter_collection->add_column_mapping(
                    $field_definition->get_name(),
                    $field_definition->get_select());
            }
            $this->filter_collection->init();
        }
        $this->initialized = true;
    }

    /**
     * @return bool
     */
    public function is_initialized()
    {
        return $this->initialized;
    }

    #endregion

    #region Query builder methods

    /**
     * Return main query without select
     *
     * @return string
     */
    protected abstract function get_query();

    /**
     * Override this method to have a specific query for counting. Example: Not including GROUP BY (which doesn't work with COUNT).
     *
     * @return string
     * @throws \Exception
     */
    protected function get_count_query()
    {
        $query = $this->get_query();
        if (strpos($query, 'GROUP') !== false) {
            throw new \Exception('Grid detected GROUP statement in count query. Please override get_count_query() and exclude group usage.');
        }

        return $query;
    }

    /**
     * Combines all field selects for SQL select
     *
     * @return string SQL select
     * @throws \moodle_exception
     */
    protected function get_query_select()
    {
        $selects = array();
        $fields = $this->get_field_definitions();

        foreach ($fields as $field) {
            if (is_null($field->get_select())) {
                continue;
            }

            $selects[] = $field->get_select() . ' AS ' . $field->get_name();
        }

        $select = implode(', ', $selects);

        if (empty($select)) {
            throw new \moodle_exception('SQL select cannot be empty.');
        }

        return $select;
    }

    /**
     * Get final SQL query and params.
     *
     * @param bool $count If true, query will be counting records instead of selecting fields.
     * @return array
     * @throws \Exception
     * @throws \moodle_exception
     */
    protected function get_sql_and_params($count = false)
    {
        global $DB;

        if (!$this->has_any_field_definitions()) {
            throw new \moodle_exception('Grid initialized without any fields. Did you forget to call report::init()?');
        }

        if (!$this->has_required_filters_applied()) {
            throw new \Exception('Missing required filters');
        }

        if ($this->get_filter_collection()->has_filters()) {
            list ($filter_sql, $filter_params) = $this->filter_collection->get_sql_and_params();
        } else {
            $filter_sql = '';
            $filter_params = [];
        }

        // Use count query and only select a count of primary field.
        if ($count) {
            $query = $this->get_count_query();
            $selects = $this->get_count_select();
            $order_by = '';
        } else {
            $query = $this->get_query();
            $selects = $this->get_query_select();
            $order_by = $this->get_sort_sql();
        }

        $query = str_replace('%%SELECT%%', $selects, $query);
        $query = str_replace('%%FILTERS%%', $filter_sql, $query);
        $query = str_replace('%%ORDERBY%%', $order_by, $query);

        return [$query, $filter_params];
    }

    #endregion

    #region Sort methods

    /**
     * Call method to handle sort params.
     *
     * @throws \coding_exception
     */
    protected function handle_sort_params()
    {
        $sort = optional_param('sort', null, PARAM_TEXT);
        $sort_direction = optional_param('sort_dir', 'asc', PARAM_TEXT);

        $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'data_grid_sort');

        if ($sort) {
            $cache->set($this->get_user()->get_id() . '_' . $this->get_name(), [
                'sort' => $sort,
                'sort_dir' => $sort_direction
            ]);
        } else {
            $data = $cache->get($this->get_user()->get_id() . '_' . $this->get_name());
            if (isset($data['sort'])) {
                $sort = $data['sort'];
            }
            if (isset($data['sort_dir'])) {
                $sort_direction = $data['sort_dir'];
            }
        }

        if ($sort) {
            foreach ($this->get_field_definitions() as $field) {
                if ($field->get_name() == $sort) {
                    $field->set_sort(true);
                    $field->set_sort_direction($sort_direction);
                    break;
                }
            }
        }
    }

    /**
     * Build ORDER BY sql for grid.
     *
     * @return string
     */
    protected function get_sort_sql()
    {
        $sql = '';
        $sorts = [];

        foreach ($this->get_field_definitions() as $field) {
            if ($field->get_sort()) {
                $sorts[] = $field->get_sort_select() . ' ' . strtoupper($field->get_sort_direction());
            }
        }

        if (!empty($sorts)) {
            $sql = 'ORDER BY ' . implode(',', $sorts);
        }

        return $sql;
    }

    #endregion

    #region Execution methods

    /**
     * Execute query and return data collection.
     *
     * @throws \moodle_exception
     * @return data_collection_interface
     * @since 2.2
     *
     */
    public function get_data()
    {
        if ($this->data_collection) {
            return $this->data_collection;
        }

        $records = $this->get_records();

        $grid_data = new data_collection();

        $field_definitions = $this->get_field_definitions();

        foreach ($records as $record) {
            $row = new data_collection();
            foreach ($field_definitions as $field_definition) {
                if (!$field_definition->get_visibility() == field_definition_interface::VISIBILITY_HIDDEN) {
                    continue;
                }

                $name = $field_definition->get_name();

                $record->$name = $field_definition->transform_data($record->$name, $record);
            }

            $row->add_data_associative($record);
            $grid_data->add_child_collection('rows', $row);
        }

        $this->data_collection = $grid_data;

        return $this->data_collection;
    }

    /**
     * Check if grid has data collected. False if grid hasn't run or requires something before it can run.
     *
     * @return bool
     */
    public function has_data()
    {
        return !empty($this->data_collection);
    }

    /**
     * Clear data so grid can query it again.
     */
    public function reset()
    {
        $this->data_collection = null;
        $this->filter_collection = new filter_collection($this);
        $this->fields = [];
        $this->record_count = null;
        $this->bulk_actions = [];
        $this->initialized = false;
    }

    /**
     * Get raw records from database.
     *
     * @return \stdClass[]
     * @throws \Exception
     * @throws \moodle_exception
     * @since 2.2
     */
    protected function get_records()
    {
        global $DB;

        list($query, $filter_params) = $this->get_sql_and_params(false);

        if ($this->supports_pagination() && !$this->is_pagination_disabled()) {
            return $DB->get_records_sql($query, $filter_params, $this->paginator->get_limit_from(), $this->paginator->get_per_page());
        }

        return $DB->get_records_sql($query, $filter_params);
    }

    /**
     * Get total number of records for pagination.
     *
     * @return int
     */
    protected function get_records_count()
    {
        global $DB;

        if (is_null($this->record_count)) {
            list($query, $filter_params) = $this->get_sql_and_params(true);

            $this->record_count = $DB->count_records_sql($query, $filter_params);
        }

        return $this->record_count;
    }

    /**
     * Get select SQL for counting.
     *
     * @return string
     */
    protected function get_count_select()
    {
        return 'COUNT(' . $this->get_field_definitions()[0]->get_select() . ')';
    }

    #endregion

    #region Filtering methods

    /**
     * @return filter_collection_interface
     */
    public function get_filter_collection()
    {
        return $this->filter_collection;
    }

    /**
     * Get all filters that are required for grid to run.
     *
     * @return filter_interface[]
     */
    public function get_required_filters()
    {
        return $this->filter_collection->get_required_filters();
    }

    /**
     * Check if grid has all required filters applied in order to run
     *
     * @return bool
     */
    public function has_required_filters_applied()
    {
        if (!$this->filter_collection->has_required_filters()) {
            return true;
        }

        $requiredfilters = $this->get_required_filters();
        $requiredfilter_names = [];

        foreach ($requiredfilters as $requiredfilter) {
            $requiredfilter_names[] = $requiredfilter->get_field_name();
        }

        $matches = 0;

        foreach ($this->filter_collection->get_applied_filters() as $filter) {
            if (in_array($filter->get_field_name(), $requiredfilter_names)) {
                $matches++;
            }
        }

        return $matches == count($requiredfilters);
    }

    /**
     * Clear user submitted filter values.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    protected function clean_filter_data(\stdClass $data)
    {
        foreach ($data as $key => $value) {
            if (is_null($value) || $value == '') {
                unset($data->$key);
            }
        }

        return $data;
    }

    /**
     * Get filter data.
     *
     * @return array
     */
    public function get_filter_data()
    {
        $filtersdata = [];
        if ($this->get_filter_collection()->has_filters()) {
            foreach ($this->filter_collection->get_applied_filters() as $filter) {
                $filtersdata[] = [
                    'field_name' => $filter->get_field_name(),
                    'value' => $filter->get_raw_value()
                ];
            }
        }

        return $filtersdata;
    }

    /**
     * Handle filter form submission by user.
     */
    public function handle_filter_submission()
    {
        $form = $this->get_form();

        // Handle user clearing filters. Essentially a "cancel" relabeled.
        if ($form->is_cancelled()) {
            $this->clear_filters();
            redirect($this->get_url());
        }

        $filter_values = new \stdClass();

        if ($data = $form->get_data()) {
            // Clear any old filter values.
            $this->clear_filters();
            $filter_values = $this->clean_filter_data($data);
        } else {
            if ($filter_data = $this->get_cached_filter_data()) {
                $filter_values = (object)$filter_data;
            }
        }

        foreach ($_GET as $param => $value) {
            if (strpos($param, 'filter_') == 0) {
                $filter_name = str_replace('filter_', '', $param);
                $filter_values->$filter_name = $value;
            }
        }

        // Set data on form now that everything is aggregated.
        $form->set_data($filter_values);

        foreach ($filter_values as $key => $data) {
            $this->apply_filter($key, $data);
        }

        $this->cache_filters();
    }

    #endregion

    #region Pagination methods

    /**
     * Override to disable pagination for this grid.
     *
     * @return bool
     */
    public function supports_pagination()
    {
        return true;
    }

    /**
     * @return int
     */
    protected function get_per_page_default()
    {
        return paginator::PER_PAGE_DEFAULT;
    }

    /**
     * Disable pagination grid.
     */
    public function disabled_pagination()
    {
        $this->disable_pagination = true;
    }

    /**
     * Enable pagination on grid.
     */
    public function enable_pagination()
    {
        $this->disable_pagination = false;
    }

    /**
     * Check if all pagination is disabled.
     *
     * @return bool
     */
    public function is_pagination_disabled()
    {
        return $this->disable_pagination;
    }

    #endregion

    #region Access control methods

    /**
     * Check if user has access to report
     *
     * @param \stdClass $user
     * @throws \Exception
     * @return bool
     */
    public function has_access(\stdClass $user)
    {
        return true;
    }

    #endregion

    #region Display/output methods

    /**
     * @param $url
     */
    public function set_url($url)
    {
        $this->url = $url;
    }

    /**
     * @return \moodle_url|string
     */
    public function get_url()
    {
        global $PAGE;

        if ($this->url) return $this->url;

        return $PAGE->url;
    }

    /**
     * @return data_grid_filter_form
     * @throws \Exception
     */
    public function get_form()
    {
        global $PAGE;

        if (!$this->form) {
            $url = clone $PAGE->url;
            // Ensure pagination goes back to 0 when submitting filters. The new result could be less than before.
            if ($this->paginator && !$this->is_pagination_disabled()) {
                $url->param($this->paginator->get_param_name(), 0);
            }
            $this->form = new data_grid_filter_form($this, $url->out(false));
        }
        return $this->form;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param \renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @throws \Exception
     * @return \stdClass|array
     */
    public function export_for_template(\renderer_base $output)
    {
        global $PAGE;

        $PAGE->requires->js_call_amd('block_dash/data_grid', 'init');

        $this->add_component('paginator', $this->paginator);

        $formhtml = '';

        if (optional_param('clear_selection', false, PARAM_BOOL)) {
            $this->clear_selections();
            $PAGE->url->param('clear_selection', '');
            redirect($PAGE->url);
        }

        if ($p = optional_param('p', null, PARAM_INT)) {
            $url = clone $PAGE->url;
            $url->param('p', $p);
            $PAGE->set_url($url);
        }

        if ($this->get_filter_collection()->has_filters()) {
            $this->handle_filter_submission();

            ob_start();
            $this->get_form()->display();
            $formhtml = ob_get_clean();
        }

        $this->handle_sort_params();

        // Ensure grid has everything it needs to run. Otherwise don't display grid html.
        // If there's missing required filters, display form in page rather than popup.
        if ($this->has_required_filters_applied()) {
            $data_collection = $this->get_data();
            $grid_html = $this->format($data_collection, 'html', '', '');
        } else {
            $grid_html = '';
        }


        if (!is_string($grid_html)) {
            throw new \Exception('Data grid expected HTML format.');
        }

        if ($this->get_filter_collection()->has_filters()) {
            $applied_filter_count = count($this->get_applied_filters());
        } else {
            $applied_filter_count = 0;
        }

        if (!$this->has_data() || !$this->supports_pagination()) {
            $this->remove_component('paginator');
        }

        $url = clone $PAGE->url;
        $url->param('clear_selection', true);

        $bulk_actions_html = '';
        foreach ($this->get_bulk_actions() as $bulk_action) {
            if ($bulk_action->is_allowed($this->get_user())) {
                $bulk_actions_html .= $bulk_action->render();
            }
        }

        $grid_actions_html = '';
        foreach ($this->get_grid_actions() as $grid_action) {
            if ($grid_action->is_allowed($this->get_user())) {
                $grid_actions_html .= $grid_action->render();
            }
        }

        $name = str_replace('.', '_', $this->get_name());

        return [
            'name' => $name,
            'data' => json_encode($this->jsonSerialize()),
            'has_data' => $this->has_data(),
            'supports_pagination' => $this->supports_pagination(),
            'supports_selection' => $this->supports_selection(),
            'clear_selection_url' => $url,
            'selection_count' => count($this->get_selections()),
            'grid_html' => $grid_html,
            'filter_form_html' => $formhtml,
            'has_filters' => $this->get_filter_collection()->has_filters(),
            'applied_filter_count' => $applied_filter_count,
            'has_required_filters_applied' => $this->has_required_filters_applied(),
            'grid_actions_html' => $grid_actions_html,
            'has_grid_actions' => !empty($grid_actions_html),
            'bulk_actions_html' => $bulk_actions_html,
            'has_bulk_actions' => !empty($bulk_actions_html)
        ];
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->get_name(),
            'selection_count' => count($this->get_selections())
        ];
    }

    #endregion
}
