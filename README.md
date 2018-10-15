# OroEntitySerializedFieldsBundle

OroEntitySerializedFieldsBundle extends [OroEntityExtendBundle](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/EntityExtendBundle) features with a new "Serialized field" custom field storage type that enables admin users to modify extended entities with new custom fields without updating the database schema.

## Table of content

- [Fundamentals](#fundamentals)
- [Requirements](#requirements)
- [Installation](#installation)
- [Run unit tests](#run-unit-tests)


## Fundamentals

OroPlatform provides the ability to have custom entities or extend entities with new custom fields.

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

To create a serialized field via migration the [SerializedFieldsExtension](./Migration/Extension/SerializedFieldsExtension.php) can be used. Here is an example:

```php
<?php

namespace Acme\Bundle\AppBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSerializedFieldMigration implements
    Migration,
    SerializedFieldsExtensionAwareInterface
{
    /** @var SerializedFieldsExtension */
    protected $serializedFieldsExtension;

    /**
     * {@inheritdoc}
     */
    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension)
    {
        $this->serializedFieldsExtension = $serializedFieldsExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->serializedFieldsExtension->addSerializedField(
            $schema->getTable('my_table'),
            'my_serialized_field',
            'string',
            [
                'extend'    => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ]
            ]
        );
    }
}
```

## Requirements

OroEntitySerializedFieldsBundle requires OROPlatform(BAP) and PHP 7.1 or above.


## Installation

Package is available through Oro Package Manager.
For development purposes it can be cloned directly from the GitHub repository.

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
php bin/console oro:platform:update --force
```

## Run unit tests

To run unit tests for this package:

```bash
phpunit -c PACKAGE_ROOT/phpunit.xml.dist
```
