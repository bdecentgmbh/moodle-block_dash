<?php


namespace block_dash\configuration;

use block_dash\source\sql_data_source;
use block_dash\source\testing_data_source;
use block_dash\table\course_table;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        $config = $block_instance->config;

        $data_source = new sql_data_source(new course_table());

        return new configuration($block_instance->context, $data_source, $config->mustache);
    }
}
