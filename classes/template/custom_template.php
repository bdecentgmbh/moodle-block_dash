<?php


namespace block_dash\template;


use block_dash\block_builder;
use block_dash\data_grid\field\field_definition_interface;
use block_dash\data_grid\filter\filter_collection;
use block_dash\data_grid\filter\filter_collection_interface;

class custom_template extends abstract_template
{
    const LAYOUT_TYPE_PATH = 1;
    const LAYOUT_TYPE_RAW_MUSTACHE = 2;

    /**
     * @var \stdClass
     */
    private $record;

    protected function __construct(\stdClass $record, \context $context)
    {
        parent::__construct($context);
        $this->record = $record;
        $this->record->available_field_definitions = json_decode($record->available_field_definitions, true);
    }

    /**
     * @return string
     */
    public function get_query_template()
    {
        return $this->record->query_template;
    }

    /**
     * @return filter_collection_interface
     */
    public function get_filter_collection()
    {
        return new filter_collection();
    }

    /**
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public function get_available_field_definitions()
    {
        $field_definitions = [];

        foreach (block_builder::get_all_field_definitions() as $field_definition) {
            if (in_array($field_definition->get_name(), $this->record->available_field_definitions)) {
                $field_definitions[] = $field_definition;
            }
        }

        return $field_definitions;
    }

    /**
     * @return string
     */
    public function get_mustache_template_name()
    {
        global $CFG;

        if ($this->record->layout_type == self::LAYOUT_TYPE_PATH) {
            return $this->record->layout_path;
        } else if ($this->record->layout_type == self::LAYOUT_TYPE_RAW_MUSTACHE) {

            make_localcache_directory('block_dash/templates');

            $path = "$CFG->localcachedir/block_dash/templates/" . $this->record->idnumber;

            if (!file_exists($path) || md5(file_get_contents($path)) != md5($this->record->layout_mustache)) {
                file_put_contents($path, $this->record->layout_mustache);
            }

            return '_custom/' . $this->record->idnumber;
        }

        return 'block_dash/layout_missing';
    }

    public static function create(\stdClass $record, \context $context)
    {
        return new custom_template($record, $context);
    }
}
