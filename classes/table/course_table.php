<?php


namespace block_dash\table;


use block_dash\table\field\join;

class course_table implements table_interface
{
    /**
     * Return name of table
     *
     * @return string
     */
    public function get_name()
    {
        return 'course';
    }

    /**
     * Return unique table alias.
     *
     * @return string
     */
    public function get_alias()
    {
        return 'c';
    }

    public function get_joins()
    {
        return [
            new join(course_category_table::class, 'category', 'id'),
            new join(enrol_table::class, 'id', 'courseid')
        ];
    }

    /**
     * @return array
     */
    public function get_field_definitions()
    {
        return [

        ];
    }

}
