<?php


namespace block_dash\data;


class testing_data_source implements data_source_interface
{
    public function get_data_collection()
    {
        global $DB;

        $data_collection = new data_collection();

        $courses = $DB->get_records('course');

        foreach ($courses as $course) {
            $course_data_collection = new data_collection();
            foreach ($course as $fieldname => $fieldvalue) {
                $course_data_collection->add_data(new field($fieldname, $fieldvalue));
            }
            $data_collection->add_child_collection('courses', $course_data_collection);
        }

        return $data_collection;
    }
}
