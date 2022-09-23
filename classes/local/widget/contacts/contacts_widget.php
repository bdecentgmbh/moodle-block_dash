<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contacts widget class contains the layout information and generate the data for widget.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\contacts;

use block_dash\local\widget\abstract_widget;
use context_block;
use moodle_url;

/**
 * Contacts widget contains list of available contacts.
 */
class contacts_widget extends abstract_widget {

    /**
     * Get the name of widget.
     *
     * @return void
     */
    public function get_name() {
        return get_string('widget:mycontacts', 'block_dash');
    }

    /**
     * Check the widget support uses the query method to build the widget.
     *
     * @return bool
     */
    public function supports_query() {
        return false;
    }

    /**
     * Layout class widget will use to render the widget content.
     *
     * @return \abstract_layout
     */
    public function layout() {
        return new contacts_layout($this);
    }

    /**
     * Pre defined preferences that widget uses.
     *
     * @return array
     */
    public function widget_preferences() {
        $preferences = [
            'datasource' => 'contacts',
            'layout' => 'contacts',
        ];
        return $preferences;
    }

    /**
     * Build widget data and send to layout thene the layout will render the widget.
     *
     * @return void
     */
    public function build_widget() {
        global $USER, $DB, $PAGE;
        static $jsincluded = false;
        $userid = $USER->id;
        $contactslist = (method_exists('\core_message\api', 'get_user_contacts'))
            ? \core_message\api::get_user_contacts($userid) : \core_message\api::get_contacts($userid);

        if (defined('\core_message\api::MESSAGE_ACTION_READ')) {
            $unreadcountssql = 'SELECT DISTINCT m.useridfrom, m.conversationid, count(m.id) as unreadcount
                                FROM {messages} m
                            INNER JOIN {message_conversations} mc
                                    ON mc.id = m.conversationid
                            INNER JOIN {message_conversation_members} mcm
                                    ON m.conversationid = mcm.conversationid
                            LEFT JOIN {message_user_actions} mua
                                    ON (mua.messageid = m.id AND mua.userid = ? AND
                                    (mua.action = ? OR mua.action = ?))
                                WHERE mcm.userid = ?
                                AND m.useridfrom != ?
                                AND mua.id is NULL
                            GROUP BY m.useridfrom';
            $unreadcounts = $DB->get_records_sql($unreadcountssql,
                [$userid, \core_message\api::MESSAGE_ACTION_READ, \core_message\api::MESSAGE_ACTION_DELETED,
                $userid, $userid]
            );
        } else {
            $unreadcountssql = 'SELECT useridfrom, count(*) as count
                                FROM {message}
                                WHERE useridto = ?
                                    AND timeusertodeleted = 0
                                    AND notification = 0
                                GROUP BY useridfrom';
            $unreadcounts = $DB->get_records_sql($unreadcountssql, [$userid]);
        }

        array_walk($contactslist, function($value) use ($unreadcounts) {
            $muserid = (isset($value->id)) ? $value->id : $value->userid; // Totara support.
            $value->contacturl = new \moodle_url('/message/index.php', ['id' => $muserid]);
            $user = \core_user::get_user($muserid);
            if ($user->picture == 0) {
                $value->profiletext = ucwords($value->fullname)[0];
            }
            $value->unreadcount = isset($unreadcounts[$user->id]) ?
                (isset($unreadcounts[$user->id]->unreadcount)
                    ? $unreadcounts[$user->id]->unreadcount : $unreadcounts[$user->id]->count) : false;
            $value->profileurl = new \moodle_url('/user/profile.php', ['id' => $muserid]);
            $value->id = $muserid; // Totara support.
        });

        $contextid = $this->get_block_instance()->context->id;
        $this->data = (!empty($contactslist)) ? ['contacts' => array_values($contactslist), 'contextid' => $contextid] : [];

        if (!$jsincluded) {
            $PAGE->requires->js_call_amd('block_dash/contacts', 'init', ['contextid' => $contextid]);
            $jsincluded = true;
        }

        return $this->data;
    }

    /**
     * Load groups that the contact user and the loggedin user in same group.
     *
     * @param context_block $context
     * @param stdclass $args
     * @return string $table html
     */
    public function load_groups($context, $args) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/grouplib.php');
        $contactuserid = (int) $args->contactuser;

        if (block_dash_is_totara()) {
            $table = new \block_dash\table\groups_totara($context->instanceid);
            $table->set_filterset($contactuserid);
        } else {
            $filterset = new \block_dash\table\groups_filterset('dash-groups-'.$context->id);
            $contactuser = new \core_table\local\filter\integer_filter('contactuser');
            $contactuser->add_filter_value($contactuserid);
            $filterset->add_filter($contactuser);

            $table = new \block_dash\table\groups($context->instanceid);
            $table->set_filterset($filterset);
        }

        ob_start();
        echo \html_writer::start_div('dash-widget-table');
        $table->out(10, true);
        echo \html_writer::end_div();
        $tablehtml = ob_get_contents();
        ob_end_clean();

        return $tablehtml;
    }
}
