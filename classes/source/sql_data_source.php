<?php


namespace block_dash\source;


use block_dash\data\data_collection;
use block_dash\data\data_collection_interface;
use block_dash\table\table_interface;

class sql_data_source implements data_source_interface
{
    /**
     * @var table_interface
     */
    private $base_table;

    private $joined_tables = [];

    public function __construct(table_interface $base_table)
    {
        $this->base_table = $base_table;
    }

    public function build_sql()
    {
        $sql = 'SELECT * FROM {' . $this->base_table->get_name() . '} ' . $this->base_table->get_alias();

        $sql .= $this->build_joins($this->base_table);

        return $sql;
    }

    protected function build_joins(table_interface $table, $sql = '')
    {
        foreach ($table->get_joins() as $join) {
            $join_table_class = $join->get_reference();
            /** @var table_interface $table */
            $jointable = new $join_table_class();

            if (in_array($join_table_class, $this->joined_tables)) {
                continue;
            }

            $this->joined_tables[] = $join_table_class;

            $sql .= sprintf(' JOIN {%s} AS %s ON %s.%s = %s.%s',
                $jointable->get_name(),
                $jointable->get_alias(),
                $jointable->get_alias(),
                $join->get_primary(),
                $table->get_alias(),
                $join->get_foreign()
            );

            $sql = $this->build_joins($jointable, $sql);
        }

        return $sql;
    }

    /**
     * @return data_collection_interface
     */
    public function get_data_collection()
    {

        global $DB;

        $all_data = new data_collection();

        foreach ($DB->get_records_sql($this->build_sql(), [], 0, 100) as $record) {
            $data_collection = new data_collection();
            $data_collection->add_data_associative((array)$record);
            $all_data->add_child_collection('rows', $data_collection);
        }

        return $all_data;
    }
}
