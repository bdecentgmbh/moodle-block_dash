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
 * Fields shared by the gallery forms.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\form;

use block_dash\gallery\gallery;
use block_dash\gallery\item;

/**
 * Fields shared by the gallery forms.
 *
 * @package    block_dash
 */
trait gallery_form_trait {
    /**
     * Add name, shortname and description fields for a site item.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function add_details_fields(\MoodleQuickForm $mform): void {
        $mform->addElement('text', 'name', get_string('galleryname', 'block_dash'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'shortname', get_string('galleryshortname', 'block_dash'), ['size' => 30]);
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addHelpButton('shortname', 'galleryshortname', 'block_dash');

        $mform->addElement('textarea', 'description', get_string('description'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);
    }

    /**
     * Add the embed settings: the flag and the roles that may embed the item.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function add_embed_fields(\MoodleQuickForm $mform): void {
        $mform->addElement('advcheckbox', 'embeddable', get_string('galleryembeddable', 'block_dash'));
        $mform->addHelpButton('embeddable', 'galleryembeddable', 'block_dash');

        $roles = [];
        foreach (role_get_names(\context_system::instance()) as $role) {
            if ($role->archetype !== 'frontpage') {
                $roles[$role->id] = $role->localname;
            }
        }
        $mform->addElement('autocomplete', 'embedroles', get_string('galleryembedroles', 'block_dash'), $roles, [
            'multiple' => true,
            'noselectionstring' => get_string('galleryembedrolesany', 'block_dash'),
        ]);
        $mform->addHelpButton('embedroles', 'galleryembedroles', 'block_dash');
        $mform->hideIf('embedroles', 'embeddable', 'notchecked');

        $mform->addElement('select', 'embedrolecontext', get_string('restrictrolecontext', 'block_dash'), [
            item::ROLES_ANY_CONTEXT => get_string('any'),
            item::ROLES_SYSTEM => get_string('coresystem'),
        ]);
        $mform->hideIf('embedrolecontext', 'embeddable', 'notchecked');
    }

    /**
     * Check a shortname for a site item.
     *
     * @param string $shortname
     * @param int $exceptid Id of the item being renamed.
     * @return string|null Error message.
     */
    protected function validate_shortname(string $shortname, int $exceptid = 0): ?string {
        $error = gallery::validate_shortname($shortname, $exceptid);
        return $error ? get_string($error, 'block_dash') : null;
    }

    /**
     * The embed settings from submitted data, as gallery::update_settings() takes them.
     *
     * @param \stdClass $data
     * @return array
     */
    protected function get_embed_settings(\stdClass $data): array {
        return [
            'embeddable' => !empty($data->embeddable),
            'embedroles' => !empty($data->embedroles) ? (array) $data->embedroles : [],
            'embedrolecontext' => (int) ($data->embedrolecontext ?? item::ROLES_ANY_CONTEXT),
        ];
    }
}
