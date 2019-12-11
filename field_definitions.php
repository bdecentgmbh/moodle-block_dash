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
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$definitions = [
    [
        'name' => 'u_id',
        'select' => 'u.id',
        'title' => get_string('user') . ' ID',
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\identifier_attribute::class
            ]
        ]
    ],
    [
        'name' => 'u_firstname',
        'select' => 'u.firstname',
        'title' => get_string('firstname')
    ],
    [
        'name' => 'u_lastname',
        'select' => 'u.lastname',
        'title' => get_string('lastname')
    ],
    [
        'name' => 'u_email',
        'select' => 'u.email',
        'title' => get_string('email')
    ],
    [
        'name' => 'u_username',
        'select' => 'u.username',
        'title' => get_string('username')
    ],
    [
        'name' => 'u_idnumber',
        'select' => 'u.idnumber',
        'title' => get_string('idnumber')
    ],
    [
        'name' => 'u_city',
        'select' => 'u.city',
        'title' => get_string('city')
    ],
    [
        'name' => 'u_country',
        'select' => 'u.country',
        'title' => get_string('country')
    ],
    [
        'name' => 'u_lastlogin',
        'select' => 'u.lastlogin',
        'title' => get_string('lastlogin'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\date_attribute::class
            ]
        ]
    ],
    [
        'name' => 'u_department',
        'select' => 'u.department',
        'title' => get_string('department')
    ],
    [
        'name' => 'u_institution',
        'select' => 'u.institution',
        'title' => get_string('institution')
    ],
    [
        'name' => 'u_address',
        'select' => 'u.address',
        'title' => get_string('address')
    ],
    [
        'name' => 'u_alternatename',
        'select' => 'u.alternatename',
        'title' => get_string('alternatename')
    ],
    [
        'name' => 'u_firstaccess',
        'select' => 'u.firstaccess',
        'title' => get_string('firstaccess'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\date_attribute::class
            ]
        ]
    ],
    [
        'name' => 'u_description',
        'select' => 'u.description',
        'title' => get_string('description')
    ],
    [
        'name' => 'u_picture_url',
        'select' => 'u.picture',
        'title' => get_string('pictureofuser'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\user_image_url_attribute::class
            ]
        ]
    ],
    [
        'name' => 'u_picture',
        'select' => 'u.picture',
        'title' => get_string('pictureofuser'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\user_image_url_attribute::class
            ],
            [
                'type' => \block_dash\data_grid\field\attribute\image_attribute::class,
                'options' => [
                    'title' => get_string('pictureofuser')
                ]
            ]
        ]
    ],
    [
        'name' => 'u_profile_url',
        'select' => 'u.id',
        'title' => get_string('userprofileurl', 'block_dash'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])
                ]
            ]
        ]
    ],
    [
        'name' => 'u_profile_link',
        'select' => 'u.id',
        'title' => get_string('userprofilelink', 'block_dash'),
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\moodle_url_attribute::class,
                'options' => [
                    'url' => new moodle_url('/user/profile.php', ['id' => 'u_id'])
                ]
            ],
            [
                'type' => \block_dash\data_grid\field\attribute\link_attribute::class,
                'options' => [
                    'label' => get_string('viewprofile')
                ]
            ]
        ]
    ],
];

require_once("$CFG->dirroot/user/profile/lib.php");

$i = 0;
foreach (profile_get_custom_fields() as $custom_field) {
    $definitions[] = [
        'name' => 'u_pf_' . strtolower($custom_field->shortname),
        'select' => "(SELECT profile$i.data FROM {user_info_data} profile$i
                      WHERE profile$i.userid = u.id AND profile$i.fieldid = $custom_field->id)",
        'title' => $custom_field->name
    ];

    $i++;
}

$definitions = array_merge($definitions, [
    [
        'name' => 'g_id',
        'select' => 'g.id',
        'title' => get_string('group') . ' ID',
        'attributes' => [
            [
                'type' => \block_dash\data_grid\field\attribute\identifier_attribute::class
            ]
        ]
    ],
    [
        'name' => 'g_name',
        'select' => 'g.name',
        'title' => get_string('group')
    ]
]);

return $definitions;