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
* **Query template**: Query templates are written by developers to define which data to include. These are typically joins,
because selects, wheres, ordering, etc is handled elsewhere (and is often dynamic). 

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

# Dash Framework

Dash comprises smaller components that live inside the Dash Framework. These components generic, decoupled, and extendable APIs. A Dash Framework API must be unit tested and well documented.

Dash Framework APIs live in the `<component>\local\dash_framework` namespace of a plugin.

### List of standard Dash Framework APIs

#### [Query builder](#query-builder)

Generate SQL queries to be run by Moodle's Data API

#### [Filtering](#filtering)

Create generic filters.

### Creating a new Dash Framework API 

#### Step 1 - Determine if your code should be an API

Any reusable chunk of functionality can be used as an API. Think of the Moodle File API or Custom Field API. These APIs provide generic functionality to be utilized in specific ways. 

Unit testing will also reveal how portable your API code is. If you cannot test without tightly coupled dependencies, then perhaps the code should be business logic inside of a plugin, rather than a reusable API.

#### Step 2 - Create a new folder for your framework API classes

Add a new folder in `<pluginfolder>/classes/local/dash_framework`

As an example: `blocks/dash/classes/local/dash_framework/result_cache`

#### Step 3 - Create unit tests

Prefix your unit test class with `dash_framework_` and try to keep all tests within a single class. Follow this convention:

`<pluginfolder>/tests/dash_framework_result_cache_test.php`

Strive for full code coverage on your API and make changes that are backwards compatible.

#### Step 4 - Write the API code

"API" is used loosely in this documentation. Create friendly and easy to use PHP classes within your framework namespace. Here's a simple example:

```php
namespace block_dash\local\dash_framework\result_cache;

interface cacher {
    
    public function set(string $cache_identifier, array $datatocache): void; // Use typehints and "SOLID" practices.

    public function get(string $cache_identifier): array; // Public functions should be easy to use methods of consuming.
}
```

## Query builder

Under the hood Dash builds queries using a strict API for constructing queries. This API is decoupled from the rest of a dash's lifecycle.

Simple example:

```php
use block_dash\local\dash_framework\query_builder\builder;

$builder = new builder();
$results = $builder->select('c.id', 'c_id') // Column aliasing.
    ->from('course', 'c') // Table aliasing.
    ->query();
```

### WHERE clauses

Filter results by adding where clauses.

### Results caching (TODO)

Results caching works by taking a snapshot of a query result. And storing the following info:

* md5 hash of the raw query
* Raw database results
* When the query results were cached
* When to invalidate the cache and query database

## Filtering

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
