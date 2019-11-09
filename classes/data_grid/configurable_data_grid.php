<?php


namespace block_dash\data_grid;


class configurable_data_grid extends data_grid
{
    /**
     * @var string
     */
    private $query_template;

    public function set_query_template($query_template)
    {
        $this->query_template = $query_template;
    }

    /**
     * Return main query without select
     *
     * @return string
     */
    protected function get_query()
    {
        return $this->query_template;
    }
}
