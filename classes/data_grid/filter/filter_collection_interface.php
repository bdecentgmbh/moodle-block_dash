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
interface filter_collection_interface
{
    /**
     * Initialize all filters.
     */
    public function init();

    /**
     * @return string
     */
    public function get_unique_identifier();

    /**
     * @param filter_interface $filter
     */
    public function add_filter(filter_interface $filter);

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter_interface $filter
     * @return bool
     */
    public function remove_filter(filter_interface $filter);

    /**
     * Check if collection has any filters added.
     *
     * @return bool
     */
    public function has_filters();

    /**
     * Get all filters.
     *
     * @return filter_interface[]
     */
    public function get_filters();

    /**
     * Check if a filter exists in this collection.
     *
     * @param $field_name
     * @return bool
     */
    public function has_filter($field_name);

    /**
     * Get a filter by field name.
     *
     * @param $field_name
     * @return filter_interface|null
     */
    public function get_filter($field_name);

    /**
     * Set a filter value.
     *
     * @param string $field_name
     * @param mixed $value
     * @return bool
     */
    public function apply_filter($field_name, $value);
    /**
     * Get filters with user submitted values.
     *
     * @return filter_interface[]
     */
    public function get_applied_filters();

    /**
     * Get filters with user submitted values, along with filters that have default
     * values.
     *
     * @return array
     */
    public function get_filters_with_values();

    /**
     * Check if filter collection contains any required filters.
     *
     * @return bool
     */
    public function has_required_filters();

    /**
     * Get all filters that are required for this grid.
     *
     * @return filter[]
     */
    public function get_required_filters();

    /**
     * @return array
     */
    public function get_sql_and_params();

    /**
     * Cache filter data.
     *
     * @param \stdClass $user User to cache filter preferences for.
     */
    public function cache(\stdClass $user);

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user);

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     */
    public function delete_cache(\stdClass $user);
}
