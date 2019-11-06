<?php


namespace block_dash\table\field;


class join
{
    private $reference;

    private $foreign;

    private $primary;

    public function __construct($reference, $foreign, $primary)
    {
        $this->reference = $reference;
        $this->foreign = $foreign;
        $this->primary = $primary;
    }

    /**
     * @return string
     */
    public function get_reference()
    {
        return $this->reference;
    }

    /**
     * @return string
     */
    public function get_foreign()
    {
        return $this->foreign;
    }

    /**
     * @return string
     */
    public function get_primary()
    {
        return $this->primary;
    }
}
