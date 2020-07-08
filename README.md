Dash is a block which can be added anywhere on your moodle site to display various information, e.g. the current user’s profile information or a list of users in the current course. It is built on a powerful engine which supports various data sources and templates.

The free version of dash comes with the users’ data source and a table layout.

It provides the following fields:

* First name
* Surname
* Full name
* Full name (linked to profile)
* Email address
* Username
* ID number
* City/town
* Country
* Last login
* Department
* Institution
* Address
* Alternate name
* First access
* Description
* User picture URL
* User picture
* User picture (linked to profile)
* User profile URL
* User profile link
* Message URL
* Message
* Group name

The following filters are available:

* Group
* Department
* Institution
* Last login
* First access

And the following conditions are available:

* Logged in user
* My participants / students
* My groups
* Current course
* Current course groups

Typical use cases:

*Display the participants of the current course
*Display the current user’s profile information, with support for custom profile fields
*List my classmates (everybody who is in the same group)
*List all students of a teacher (all participants of all courses where the current user has a teacher role)
*List all participants including their picture and group

# Development

### Key terms

* **Data source**: A data source defines which query, fields, and filters are used to retrieve data from a data grid.
* **Field definition**: Represents a predefined field that can be added to a data grid.
* **Field attribute**: An attribute changes how field definition output is formatted. 
* **Data grid**: Get data to be displayed in a grid or downloaded as a formatted file.

### Field definitions

#### Define custom fields
lib.php
```php
/**
 * Register field definitions.
 *
 * @return array
 */
function pluginname_register_field_definitions() {
    global $CFG;
    
    return require("$CFG->dirroot/plugintype/pluginname/field_definitions.php");
}
```
field_definitions.php
```php
return [
    [ // Field definition.
        'name' => 'u_id',
        'select' => 'u.id',
        'title' => get_string('user') . ' ID',
        'attributes' => [
            [
                'type' => \block_dash\local\data_grid\field\attribute\identifier_attribute::class
            ]
        ],
        'tables' => ['u']
    ],
];
```

#### Supporting multiple DB types
If your field definition requires something DB specific, use `select_<dbtype>`.  
```php
[ // Field definition.
    'name' => 'subquery',
    'select_mysqli' => '(SELECT GROUP_CONCAT())', // Used when Moodle is running MySQL/MariaDB
    'select_pgsql' => '(SELECT STRING_AGG())' // or PostgreSQL
]
```

### Field attributes

#### Rename object ID to object field
If a field value is an ID, you can add the `rename_ids_attribute` attribute and define how to map IDs to object fields.
This is useful when transforming multiple objects.

The following field definition transforms `1,5,123` into `Group 1, Group 5, Group 123`

```php
[
    'name' => 'u_group_names',
    'select' => "(SELECT string_agg(g200.id::text, ',') FROM {groups} g200 JOIN {groups_members} gm200 ON gm200.groupid = g200.id AND gm200.userid = u.id)",
    'title' => get_string('group'),
    'tables' => ['u'],
    'attributes' => [
        [
            'type' => \block_dash\local\data_grid\field\attribute\rename_ids_attribute::class,
            'options' => [
                'table' => 'groups',
                'field' => 'name',
                'delimiter' => ',' // Separator between each ID in SQL select.
            ]
        ]
    ]
]
```

## Change log

### 1.0.2

* Fix column sorting bug where pagination would unintentionally toggle sort direction
* Add Select2 library for autocomplete dropdowns

### 1.0.1

* Add support for PostgreSQL
* Improve filtering
* Add renaming field attribute to map objects to IDs returned by query
* Moved custom classes to `local` namespace
* Improved CSS selectors
