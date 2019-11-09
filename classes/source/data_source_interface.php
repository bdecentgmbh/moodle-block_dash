<?php


namespace block_dash\source;

use block_dash\data_grid\data\data_collection_interface;

interface data_source_interface
{
    /**
     * @return data_collection_interface
     */
    public function get_data_collection();
}
