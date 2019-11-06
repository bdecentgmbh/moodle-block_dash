<?php


namespace block_dash\configuration;

use block_dash\source\data_source_interface;

interface configuration_interface
{
    /**
     * @return \context
     */
    public function get_context();

    /**
     * @return data_source_interface
     */
    public function get_data_source();

    /**
     * @return string
     */
    public function get_template();

    /**
     * Create new configuration instance
     *
     * @param \block_base $block_instance
     * @return configuration_interface
     */
    public static function create_from_instance(\block_base $block_instance);
}
