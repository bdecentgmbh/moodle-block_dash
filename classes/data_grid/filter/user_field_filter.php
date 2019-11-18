<?php


namespace block_dash\data_grid\filter;


class user_field_filter extends select_filter
{
    /**
     * @var string
     */
    private $user_field;

    public function __construct($name, $select, $user_field)
    {
        $this->user_field = $user_field;

        parent::__construct($name, $select);
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init()
    {
        global $DB;

        $user_field = $this->user_field;

        $data = $DB->get_records_sql_menu("SELECT DISTINCT $user_field, $user_field AS value 
                                           FROM {user} where $user_field <> ''");

        $this->add_options($data);

        parent::init();
    }
}