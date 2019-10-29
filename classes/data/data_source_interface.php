<?php


namespace block_dash\data;


interface data_source_interface
{
    /**
     * @return data_collection_interface
     */
    public function get_data_collection();
}
