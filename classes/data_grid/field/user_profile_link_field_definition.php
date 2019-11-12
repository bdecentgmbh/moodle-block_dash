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
}
