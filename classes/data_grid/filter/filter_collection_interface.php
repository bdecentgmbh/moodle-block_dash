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
     * Map a single field name to a database column.
     *
     * @param string $field_name
     * @param string $database_column
     */
    public function add_column_mapping($field_name, $database_column);

    /**
     * @param filter $filter
     */
    public function add_filter(filter $filter);

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter $filter
     * @return bool
     */
    public function remove_filter(filter $filter);

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
     * @param \MoodleQuickForm $form
     * @param string $element_name_prefix
     * @throws \coding_exception
     */
    public function create_form_elements(\MoodleQuickForm &$form, $element_name_prefix = '');

    /**
     * Cache filter data.
     *
     * @param \stdClass $user User to cache filter preferences for.
     * @param string $unique_identifier Unique name for cache.
     */
    public function cache(\stdClass $user, $unique_identifier);

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @param $unique_identifier
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user, $unique_identifier);

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     * @param string $unique_identifier
     */
    public function delete_cache(\stdClass $user, $unique_identifier);
}
