<?php


namespace block_dash\data;


class field implements field_interface
{
    private $name;

    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_value()
    {
        return $this->value;
    }
}
