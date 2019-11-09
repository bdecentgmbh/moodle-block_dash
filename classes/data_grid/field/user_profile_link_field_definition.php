<?php


namespace block_dash\data_grid\field;

class user_profile_link_field_definition extends field_definition
{
    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param $data
     * @param \stdClass $record Entire row
     * @return mixed
     */
    public function transform_data($data, \stdClass $record)
    {
        if ($data) {
            return new \moodle_url('/user/profile.php', ['id' => $data]);
        }

        return '';
    }

    /**
     * @param array $tables Required tables for this field to display.
     * @param $name
     * @param $select
     * @param $title
     * @param int $visibility
     * @param array $options
     * @return field_definition
     */
    public static function create($tables, $name, $select, $title, $visibility = self::VISIBILITY_VISIBLE, $options = [])
    {
        return new user_profile_link_field_definition($tables, $name, $select, $title, $visibility, $options);
    }
}
