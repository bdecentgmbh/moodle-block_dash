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
     * @var filter_interface[] Every filter that belongs to this collection.
     */
    private $filters = [];

    /**
     * @var string
     */
    private $unique_identifier;

    /**
     * @var \context
     */
    private $context;

    /**
     * @param string $unique_identifier
     * @param \context $context
     */
    public function __construct($unique_identifier, \context $context)
    {
        $this->unique_identifier = $unique_identifier;
        $this->context = $context;
    }

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
     * @return string
     */
    public function get_unique_identifier()
    {
        return $this->unique_identifier;
    }

    /**
     * @param filter_interface $filter
     */
    public function add_filter(filter_interface $filter)
    {
        $filter->set_context($this->context);
        $this->filters[] = $filter;
    }

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter_interface $filter
     * @return bool
     */
    public function remove_filter(filter_interface $filter)
    {
        foreach ($this->filters as $key => $_filter) {
            if ($filter->get_name() == $_filter->get_name()) {
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
     * @param $name
     * @return bool
     */
    public function has_filter($name)
    {
        return !empty($this->get_filter($name));
    }

    /**
     * Get a filter by field name.
     *
     * @param $name
     * @return filter_interface|null
     */
    public function get_filter($name)
    {
        foreach ($this->get_filters() as $filter) {
            if ($filter->get_name() == $name) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Set a filter value.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function apply_filter($name, $value)
    {
        // Don't apply empty values.
        if ($value === "") {
            return false;
        }
        foreach ($this->filters as $filter) {
            if ($filter->get_name() == $name) {
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
            $sql .= sprintf(' AND %s', $filter_sql);
            $params = array_merge($params, $filter_params);
        }

        return [$sql, $params];
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
     */
    public function cache(\stdClass $user)
    {
        $filter_data = [];

        foreach ($this->filters as $filter) {
            $filter_data[$filter->get_name()] = $filter->get_raw_value();
        }

        $identifier = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        $this->create_cache()->set($identifier, $filter_data);
    }

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user)
    {
        $cache = $this->create_cache();

        $identifer = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        if (!$cache->has($identifer)) {
            return [];
        }
        return $cache->get($identifer);
    }

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     */
    public function delete_cache(\stdClass $user)
    {
        $cache = $this->create_cache();

        $identifier = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        $cache->delete($identifier);
    }
}
