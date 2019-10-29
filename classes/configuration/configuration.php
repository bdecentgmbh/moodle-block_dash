<?php


namespace block_dash\configuration;

use block_dash\data\testing_data_source;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        $config = $block_instance->config;

        return new configuration($block_instance->context, new testing_data_source(), 'block_dash/layout_grid');
    }
}
