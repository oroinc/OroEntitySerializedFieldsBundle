OroEntitySerializedFieldsBundle
===============================

This document contains a little introduction into "Oro Entity Serialized Fields" package, information on how to download and install.


Table of content
----------------

- [Fundamentals](#fundamentals)
- [Requirements](#requirements)
- [Installation](#installation)
- [Run unit tests](#run-unit-tests)


Fundamentals
------------
ORO Platform provides ability to have custom entities or extend entities with new custom fields.

The package allows to avoid schema update when you create custom field. Although this field come with some restrictions.

Such fields data stores in `serialized_data` column as serialized array. Field `serialized_data` is hidden from UI in entity config page.

Not supported features:

- grid filtering and sorting
- segments and reports
- charts
- search
- relations, enums and option set field types
- data audit
- usage of such fields in Doctrine query builder

After installation (described below) a new field called **Storage Type** appears within **New field** creation page where you will be offered to choose between two storage types:

- `Table Column` option will allow to create custom field as usual;
- `Serialized field` option means that you can avoid schema update and start to use this field imediately, but should take into account, that field types are limited in this case to:
  - string
  - integer
  - smallint
  - bigint
  - boolean
  - decimal
  - date
  - datetime
  - text
  - float
  - money
  - percent

Requirements
------------

OroEntitySerializedFieldsBundle requires OROPlatform(BAP) and PHP 5.5.9 or above.


Installation
------------

Package is available through Oro Package Manager.
For development purposes it might be cloned from github repository directly.

```
git clone git@github.com:orocrm/OroEntitySerializedFieldsBundle.git
git submodule init
git submodule update
```

Update your composer.json with 

```
   "autoload": {
        "psr-0": {
...
            "Oro\\Bundle": ["src/Oro/src", "src/OroPackages/src"],
...            
        }
    },
```

```
php composer.phar update
php app/console oro:platform:update --force
```

Run unit tests
--------------

To run unit tests for this package:

```bash
phpunit -c PACKAGE_ROOT/phpunit.xml.dist
```
