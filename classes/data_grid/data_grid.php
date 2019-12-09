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

/**
 * Get data to be displayed in a grid or downloaded as a formatted file.
 *
 * @package block_dash
 */
abstract class data_grid implements data_grid_interface, \JsonSerializable
{
    #region Properties

    /**
     * @var bool If the data grid definition has been built yet.
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
     * @var string
     */
    private $url = null;

    /**
     * @var \stdClass $user User displaying data (logged in user).
     */
    private $user;

    /**
     * @var bool Disable any kind of pagination and return full data set. Useful for downloading all data.
     */
    private $disable_pagination = false;

    /**
     * @var paginator
     */
    private $paginator;

    /**
     * @var bool
     */
    private $supportspagination;

    /**
     * @var filter_collection_interface
     */
    private $filter_collection;

    #endregion

    /**
     * @param \context $context
     * @param \stdClass $user User displaying data (logged in user).
     * @throws \coding_exception
     */
    public function __construct(\context $context, \stdClass $user = null)
    {
        global $USER;

        if (is_null($user)) {
            $this->user = $USER;
        }

        $this->context = $context;

        $this->paginator = new paginator(function () {
            return $this->get_count();
        });
    }

    /**
     * @return filter_collection_interface
     */
    public function get_filter_collection()
    {
        return $this->filter_collection;
    }

    /**
     * @param filter_collection_interface $filter_collection
     */
    public function set_filter_collection(filter_collection_interface $filter_collection)
    {
        $this->filter_collection = $filter_collection;
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

    /**
     * @return paginator
     */
    public function get_paginator()
    {
        return $this->paginator;
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
    public function has_field_definition($name)
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

    #endregion

    #region Execution methods

    /**
     * Execute and return data collection.
     *
     * @throws \moodle_exception
     * @return data_collection_interface
     * @since 2.2
     */
    public abstract function get_data();

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
     * Get total number of records for pagination.
     *
     * @return int
     */
    public abstract function get_count();

    #endregion

    #region Pagination methods

    /**
     * Override to disable pagination for this grid.
     *
     * @param bool $support
     */
    public function set_supports_pagination($support)
    {
       $this->supportspagination = $support;
    }

    /**
     * @return bool If data grid should use pagination to limit results.
     */
    public function supports_pagination()
    {
        return $this->supportspagination;
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

    public function jsonSerialize()
    {
        return [
            'name' => $this->get_name(),
            'selection_count' => count($this->get_selections())
        ];
    }

    #endregion
}
