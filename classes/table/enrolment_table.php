<?php


namespace block_dash\table;


use block_dash\table\field\join;

class enrolment_table implements table_interface
{
    /**
     * Return name of table
     *
     * @return string
     */
    public function get_name()
    {
        return 'user_enrolments';
    }

    /**
     * Return unique table alias.
     *
     * @return string
     */
    public function get_alias()
    {
        return 'ue';
    }

    public function get_joins()
    {
        return [
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
