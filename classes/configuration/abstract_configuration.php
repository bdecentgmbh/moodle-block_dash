<?php

namespace block_dash\configuration;

use block_dash\data\data_source_interface;

abstract class abstract_configuration implements configuration_interface
{
    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_source_interface
     */
    private $data_source;

    /**
     * @var string
     */
    private $template;

    protected function __construct(\context $context, data_source_interface $data_source, $template)
    {
        $this->context = $context;
        $this->data_source = $data_source;
        $this->template = $template;
    }

    /**
     * @return \context
     */
    public function get_context()
    {
        return $this->context;
    }

    /**
     * @return data_source_interface
     */
    public function get_data_source()
    {
        return $this->data_source;
    }

    /**
     * @return string
     */
    public function get_template()
    {
        return $this->template;
    }
}
