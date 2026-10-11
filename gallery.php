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
 * Manage the gallery of ready-made Dash setups.
 *
 * @package    block_dash
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\gallery\gallery;
use block_dash\local\data_source\data_source_factory;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$shortname = optional_param('item', '', PARAM_RAW_TRIMMED);

$context = context_system::instance();
$url = new moodle_url('/blocks/dash/gallery.php');

// Admins reach the page through the admin tree; managers without site configuration rights directly.
require_login();
if (has_capability('moodle/site:config', $context)) {
    admin_externalpage_setup('block_dash_gallery');
} else {
    require_capability('block/dash:managegallery', $context);
    $PAGE->set_context($context);
    $PAGE->set_url($url);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('gallerymanage', 'block_dash'));
    $PAGE->set_heading($SITE->fullname);
}

if ($action) {
    require_sesskey();
    $item = gallery::get_item($shortname);
    if (!$item) {
        throw new moodle_exception('invalidrecord', 'error', $url, 'block_dash_gallery');
    }

    switch ($action) {
        case 'hide':
        case 'show':
            gallery::update_settings($item, ['hidden' => $action === 'hide']);
            redirect($url);
            break;

        case 'export':
            $json = json_encode($item->get_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            send_file($json, str_replace(':', '-', $item->get_shortname()) . '.json', 0, 0, true, true, 'application/json');
            break;

        case 'delete':
            if (optional_param('confirm', 0, PARAM_BOOL)) {
                gallery::delete($item);
                redirect(
                    $url,
                    get_string('gallerydeleted', 'block_dash', $item->get_name()),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string('gallerydeleteconfirm', 'block_dash', $item->get_name()),
                new moodle_url($url, ['action' => 'delete', 'item' => $item->get_shortname(), 'confirm' => 1]),
                $url
            );
            echo $OUTPUT->footer();
            exit;
    }
}

$PAGE->requires->js_call_amd('block_dash/modal_form', 'init');
$PAGE->requires->js_call_amd('block_dash/gallery_picker', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gallerymanage', 'block_dash'));
echo html_writer::tag('p', get_string('gallerymanagedesc', 'block_dash'));

echo html_writer::tag('button', get_string('galleryupload', 'block_dash'), [
    'type' => 'button',
    'class' => 'btn btn-primary mb-3',
    'data-action' => 'block_dash-modalform',
    'data-formclass' => \block_dash\form\gallery_upload::class,
    'data-title' => get_string('galleryupload', 'block_dash'),
]);

$table = new html_table();
$table->head = [
    get_string('galleryname', 'block_dash'),
    get_string('gallerysource', 'block_dash'),
    get_string('datasource', 'block_dash'),
    get_string('galleryembeddable', 'block_dash'),
    get_string('status'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable block-dash-gallery-items';

foreach (gallery::get_items() as $item) {
    $shortname = $item->get_shortname();
    $actionurl = function (string $action) use ($url, $shortname) {
        return new moodle_url($url, ['action' => $action, 'item' => $shortname, 'sesskey' => sesskey()]);
    };

    $name = html_writer::tag('strong', s($item->get_name()), ['class' => 'd-block'])
        . html_writer::div(s($item->get_description()), 'small')
        . html_writer::tag('code', s($shortname), ['class' => 'small']);

    $info = data_source_factory::get_data_source_info($item->get_data_source());
    $datasource = $info['name'] ?? $item->get_data_source();

    if (!$item->is_available()) {
        $missing = implode(', ', $item->get_missing_components()) ?: $item->get_data_source();
        $status = html_writer::span(get_string('galleryneeds', 'block_dash', $missing), 'badge bg-warning text-dark');
    } else if ($item->is_hidden()) {
        $status = html_writer::span(get_string('hidden', 'block_dash'), 'badge bg-secondary');
    } else {
        $status = html_writer::span(get_string('visible'), 'badge bg-success');
    }

    $menu = new action_menu();
    $menu->set_kebab_trigger(get_string('actions'));
    $menu->add_secondary_action(new action_menu_link_secondary(new moodle_url('#'), null, get_string('preview'), [
        'data-action' => 'block_dash-gallery-preview',
        'data-item' => $shortname,
        'data-title' => $item->get_name(),
    ]));
    $menu->add_secondary_action(new action_menu_link_secondary(
        new moodle_url('#'),
        null,
        get_string('galleryeditdetails', 'block_dash'),
        [
            'data-action' => 'block_dash-modalform',
            'data-formclass' => \block_dash\form\gallery_details::class,
            'data-args' => json_encode(['item' => $shortname]),
            'data-title' => get_string('galleryeditdetails', 'block_dash'),
        ]
    ));
    $menu->add_secondary_action(new action_menu_link_secondary(
        $actionurl($item->is_hidden() ? 'show' : 'hide'),
        null,
        get_string($item->is_hidden() ? 'show' : 'hide')
    ));
    $exportlabel = get_string('exportblock', 'block_dash');
    $menu->add_secondary_action(new action_menu_link_secondary($actionurl('export'), null, $exportlabel));
    if (!$item->is_shipped()) {
        $menu->add_secondary_action(new action_menu_link_secondary(
            $actionurl('delete'),
            null,
            get_string('delete'),
            ['class' => 'text-danger']
        ));
    }

    $row = new html_table_row([
        $name,
        s($item->get_source_name()),
        s($datasource),
        $item->is_embeddable() ? get_string('yes') : get_string('no'),
        $status,
        $OUTPUT->render($menu),
    ]);
    if (!$item->is_available() || $item->is_hidden()) {
        $row->attributes['class'] = 'dimmed_text';
    }
    $table->data[] = $row;
}

echo html_writer::table($table);
echo $OUTPUT->footer();
