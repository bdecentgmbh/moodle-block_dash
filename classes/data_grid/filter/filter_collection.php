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

namespace block_dash\data_grid\filter;

/**
 * Container for a collection of filters.
 *
 * @package block_dash\filter
 */
class filter_collection implements filter_collection_interface
{
    /**
     * @var filter_interface[]
     */
    private $filters = [];

    /**
     * @var array A map of filter names to database column names.
     */
    private $column_mapping = [];

    /**
     * Initialize all filters.
     */
    public function init()
    {
        foreach ($this->get_filters() as $filter) {
            $filter->init();
        }
    }

    /**
     * Map a single field name to a database column.
     *
     * @param string $field_name
     * @param string $database_column
     */
    public function add_column_mapping($field_name, $database_column)
    {
        $this->column_mapping[$field_name] = $database_column;
    }

    /**
     * @param filter $filter
     */
    public function add_filter(filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter $filter
     * @return bool
     */
    public function remove_filter(filter $filter)
    {
        foreach ($this->filters as $key => $_filter) {
            if ($filter->get_field_name() == $_filter->get_field_name()) {
                unset($this->filters[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if collection has any filters added.
     *
     * @return bool
     */
    public function has_filters()
    {
        return count($this->filters) > 0;
    }

    /**
     * Get all filters.
     *
     * @return filter_interface[]
     */
    public function get_filters()
    {
        return $this->filters;
    }

    /**
     * Check if a filter exists in this collection.
     *
     * @param $field_name
     * @return bool
     */
    public function has_filter($field_name)
    {
        return !empty($this->get_filter($field_name));
    }

    /**
     * Get a filter by field name.
     *
     * @param $field_name
     * @return filter_interface|null
     */
    public function get_filter($field_name)
    {
        foreach ($this->get_filters() as $filter) {
            if ($filter->get_field_name() == $field_name) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Set a filter value.
     *
     * @param string $field_name
     * @param mixed $value
     * @return bool
     */
    public function apply_filter($field_name, $value)
    {
        // Don't apply empty values.
        if ($value === "") {
            return false;
        }
        foreach ($this->filters as $filter) {
            if ($filter->get_field_name() == $field_name) {
                $filter->set_raw_value($value);
                return true;
            }
        }

        return false;
    }

    /**
     * Get filters with user submitted values.
     *
     * @return filter_interface[]
     */
    public function get_applied_filters()
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            if ($filter->is_applied()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Get filters with user submitted values, along with filters that have default
     * values.
     *
     * @return array
     */
    public function get_filters_with_values()
    {
        $filters = [];
        foreach ($this->get_filters() as $filter) {
            if ($filter->has_raw_value() || $filter->has_default_raw_value()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Check if filter collection contains any required filters.
     *
     * @return bool
     */
    public function has_required_filters()
    {
        foreach ($this->get_filters() as $filter) {
            if ($filter->is_required()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all filters that are required for this grid.
     *
     * @return filter[]
     */
    public function get_required_filters()
    {
        $filters = [];

        foreach ($this->get_filters() as $filter) {
            if ($filter->is_required()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @return array
     */
    public function get_sql_and_params()
    {
        $params = [];
        $sql = '';
        foreach ($this->get_filters_with_values() as $filter) {
            list($filter_sql, $filter_params) = $filter->get_sql_and_params();
            // Ignore filters with no values.
            if (empty($filter_params)) continue;
            $select = $this->column_mapping[$filter->get_field_name()];
            $sql .= sprintf(' AND %s %s', $select, $filter_sql);
            $params = array_merge($params, $filter_params);
        }

        return [$sql, $params];
    }

    /**
     * @param \MoodleQuickForm $form
     * @param string $element_name_prefix
     * @throws \coding_exception
     */
    public function create_form_elements(\MoodleQuickForm &$form, $element_name_prefix = '')
    {
        if (!$this->has_filters()) {
            return;
        }

        foreach ($this->get_filters() as $filter) {
            $filter->create_form_element($form, $element_name_prefix);
        }
    }

    /**
     * Create a cache object store.
     *
     * @return \cache_session
     */
    private function create_cache()
    {
        return \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'filter_cache');
    }

    /**
     * Cache filter data.
     *
     * @param \stdClass $user User to cache filter preferences for.
     * @param string $unique_identifier Unique name for cache.
     */
    public function cache(\stdClass $user, $unique_identifier)
    {
        $filter_data = [];

        foreach ($this->filters as $filter) {
            $filter_data[$filter->get_field_name()] = $filter->get_raw_value();
        }

        $identifier = sprintf('%s-%s', $user->id, $unique_identifier);

        $this->create_cache()->set($identifier, $filter_data);
    }

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @param $unique_identifier
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user, $unique_identifier)
    {
        $cache = $this->create_cache();

        $identifer = sprintf('%s-%s', $user->id, $unique_identifier);

        if (!$cache->has($identifer)) {
            return [];
        }
        return $cache->get($identifer);
    }

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     * @param string $unique_identifier
     */
    public function delete_cache(\stdClass $user, $unique_identifier)
    {
        $cache = $this->create_cache();

        $identifier = sprintf('%s-%s', $user->id, $unique_identifier);

        $cache->delete($identifier);
    }
}
