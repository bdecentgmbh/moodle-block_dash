<?php


namespace block_dash\data_grid\filter;


class participants_condition extends condition
{
    /**
     * @var array
     */
    private $values;

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values()
    {
        if (is_null($this->values)) {
            global $USER;

            $this->values = [];

            if (!is_siteadmin()) {
                $courses = enrol_get_my_courses();

                $users = [];
                foreach ($courses as $course) {
                    $coursecontext = \context_course::instance($course->id);
                    if (has_capability('moodle/grade:viewall', $coursecontext)) {
                        if (has_capability('moodle/site:accessallgroups', $coursecontext)) {
                            $users = array_merge($users, get_enrolled_users($coursecontext));
                        } else {
                            $groups = groups_get_all_groups($course->id, $USER->id);
                            if ($groupids = array_keys($groups)) {
                                $users = array_merge($users, groups_get_groups_members($groupids));
                            }
                        }
                    }
                }

                foreach ($users as $user) {
                    if ($user->id != $USER->id) {
                        $this->values[] = $user->id;
                    }
                }
            }

            if (!$this->values) {
                $this->values = [0];
            }
        }

        return $this->values;
    }

    /**
     * @return string
     */
    public function get_label()
    {
        if ($label = parent::get_label()) {
            return $label;
        }

        return get_string('myparticipants', 'block_dash');
    }
}