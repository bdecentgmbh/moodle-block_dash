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
 * Class categories_data_source.
 *
 * @package    block_dash
 * @copyright  2024 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\dash_framework\structure\course_category_table;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\data_grid\filter\filter_collection_interface;
use coding_exception;
use context;
use local_dash\data_grid\filter\course_category_condition;

/**
 * Class categories_data_source.
 *
 * @package block_dash
 */
class categories_data_source extends abstract_data_source {

    /**
     * Constructor.
     *
     * @param context $context
     */
    public function __construct(context $context) {
        $this->add_table(new course_category_table());

        parent::__construct($context);
    }

    /**
     * Get human readable name of data source.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('category');
    }

    /**
     * Return query template for retrieving category info.
     *
     * @return builder
     * @throws coding_exception
     */
    public function get_query_template(): builder {

        $builder = new builder();
        $builder
            ->select('cc.id', 'cc_id')
            ->from('course_categories', 'cc');

        return $builder;
    }

    /**
     * Build and return filter collection.
     *
     * @return filter_collection_interface
     * @throws coding_exception
     */
    public function build_filter_collection() {

        $filtercollection = new filter_collection(get_class($this), $this->get_context());

        $filtercollection->add_filter(new course_category_condition('c_course_categories_condition', 'cc.id'));

        return $filtercollection;
    }
}
