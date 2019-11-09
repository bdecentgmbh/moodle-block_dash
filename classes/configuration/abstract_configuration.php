<?php

namespace block_dash\configuration;

use block_dash\template\template_interface;

abstract class abstract_configuration implements configuration_interface
{
    /**
     * @var \context
     */
    private $context;

    /**
     * @var string
     */
    private $template;

    protected function __construct(\context $context, template_interface $template)
    {
        $this->context = $context;
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
     * @return template_interface
     */
    public function get_template()
    {
        return $this->template;
    }

    /**
     * Check if block is ready to display content.
     *
     * @return bool
     */
    public function is_fully_configured()
    {
        return !empty($this->template);
    }
}
