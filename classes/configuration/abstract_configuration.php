<?php

namespace block_dash\configuration;

abstract class abstract_configuration implements configuration_interface
{
    /**
     * @var \context
     */
    private $context;

    /**
     * @var string
     */
    private $sql;

    /**
     * @var string
     */
    private $template;

    protected function __construct(\context $context, $sql, $template)
    {
        $this->context = $context;
        $this->sql = $sql;
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
     * @return string
     */
    public function get_sql()
    {
        return $this->sql;
    }

    /**
     * @return string
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
        return !empty($this->sql) && !empty($this->template);
    }
}
