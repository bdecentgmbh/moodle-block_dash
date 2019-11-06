<?php


namespace block_dash\table;


use block_dash\table\field\join;

class enrol_table implements table_interface
{
    /**
     * Return name of table
     *
     * @return string
     */
    public function get_name()
    {
        return 'enrol';
    }

    /**
     * Return unique table alias.
     *
     * @return string
     */
    public function get_alias()
    {
        return 'e';
    }

    public function get_joins()
    {
        return [
            new join(enrolment_table::class, 'id', 'enrolid')
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
