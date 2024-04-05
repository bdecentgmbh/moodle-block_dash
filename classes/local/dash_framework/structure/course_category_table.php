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
 * Class course category table.
 *
 * @package    block_dash
 * @copyright  2024 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\structure;

use block_dash\local\dash_framework\structure\field;
use block_dash\local\dash_framework\structure\field_interface;
use block_dash\local\dash_framework\structure\table;
use block_dash\local\data_grid\field\attribute\category_image_url_attribute;
use block_dash\local\data_grid\field\attribute\category_recent_course_attribute;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\field\attribute\image_attribute;
use block_dash\local\data_grid\field\attribute\image_url_attribute;
use block_dash\local\data_grid\field\attribute\moodle_url_attribute;
use block_dash\local\data_grid\field\attribute\link_attribute;
use block_dash\local\data_grid\field\attribute\linked_data_attribute;
use block_dash\local\data_grid\field\attribute\widget_attribute;
use lang_string;
use moodle_url;

/**
 * Class course_category_table.
 *
 * @package block_dash
 */
class course_category_table extends table {

    /**
     * Build a new table.
     */
    public function __construct() {
        parent::__construct('course_categories', 'cc');
    }

    /**
     * Get human readable title for table.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('tablealias_cc', 'block_dash');
    }

    /**
     * Define the fields available in the reports for this table data source.
     *
     * @return field_interface[]
     */
    public function get_fields(): array {
        return [
            new field('id', new lang_string('category'), $this, null, [
                new identifier_attribute(),
            ]),

            // Category name.
            new field('name', new lang_string('categoryname'), $this, 'cc.name'),

            // Category name linked.
            new field('categoryurl', new lang_string('categoryurl', 'block_dash'), $this, 'cc.name', [
                new moodle_url_attribute(['url' => new moodle_url('/course/management.php', ['categoryid' => 'cc_id'])]),
                new link_attribute(['label_field' => 'cc_name'])
            ]),

            // Category ID number.
            new field('idnumber', new lang_string('idnumber'), $this),

            // Description.
            new field('description', new lang_string('summary'), $this, 'cc.description, cc.descriptionformat', [
                new widget_attribute([
                    'callback' => function($coursecat, $data) {
                        if (!isset($coursecat->cc_descriptionformat)) {
                            $descriptionformat = FORMAT_MOODLE;
                        } else {
                            $descriptionformat = $coursecat->cc_descriptionformat;
                        }

                        $options = array('noclean' => true, 'overflowdiv' => true);
                        $context = \context_coursecat::instance($coursecat->cc_id);
                        $options['context'] = $context;
                        $text = file_rewrite_pluginfile_urls($coursecat->description,
                                'pluginfile.php', $context->id, 'coursecat', 'description', null);
                        return format_text($text, $descriptionformat, $options);
                    }
                ])
            ]),

            // Category image linked.
            new field('image_link', new lang_string('categoryimagelink', 'block_dash'), $this, 'cc.id', [
                new category_image_url_attribute(), new image_attribute(),
                new linked_data_attribute(['url' => new moodle_url('/course/management.php', ['categoryid' => 'cc_id'])]),
            ]),

            // Category image.
            new field('image', new lang_string('categoryimage', 'block_dash'), $this, 'cc.id', [
                new category_image_url_attribute(), new image_attribute(),
            ]),

            // Category image.
            new field('imageurl', new lang_string('categoryimageurl', 'block_dash'), $this, 'cc.id', [
                new category_image_url_attribute(), new image_url_attribute()
            ]),

            // Number of courses.
            new field('coursecount', new lang_string('categorycoursecount', 'block_dash'), $this, null),

            // Most recent course name (by created date).
            new field('mostrecentcourse', new lang_string('recentcoursename', 'block_dash'), $this, 'cc.id', [
                new category_recent_course_attribute()
            ]),

        ];
    }
}
