<?php


namespace block_dash\table;


use block_dash\table\field\join;

interface table_interface
{
    /**
     * Return name of table.
     *
     * @return string
     */
    public function get_name();

    /**
     * Return unique table alias.
     *
     * @return string
     */
    public function get_alias();

    /**
     * @return join[]
     */
    public function get_joins();

    /**
     * @return array
     */
    public function get_field_definitions();
}
