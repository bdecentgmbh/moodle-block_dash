<?php


namespace block_dash\table;


class course_category_table implements table_interface
{
    /**
     * Return name of table
     *
     * @return string
     */
    public function get_name()
    {
        return 'course_categories';
    }

    public function get_alias()
    {
        return 'cc';
    }

    public function get_joins()
    {
        return [];
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
