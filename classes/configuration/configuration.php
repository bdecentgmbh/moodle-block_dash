<?php


namespace block_dash\configuration;

class configuration extends abstract_configuration
{
    public static function create_from_instance(\block_base $block_instance)
    {
        $sql = null;
        $mustache = null;
        if (isset($block_instance->config->sql)) {
            $sql = $block_instance->config->sql;
        }
        if (isset($block_instance->config->mustache)) {
            $mustache = $block_instance->config->mustache;
        }

        return new configuration($block_instance->context, $sql, $mustache);
    }
}
