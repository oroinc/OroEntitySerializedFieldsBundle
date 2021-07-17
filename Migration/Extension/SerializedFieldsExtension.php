<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;

/**
 * The migration extension that helps to manage serialized fields of extended entities.
 */
class SerializedFieldsExtension
{
    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    public function __construct(ExtendOptionsManager $extendOptionsManager)
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }

    /**
     * Adds serialized field.
     *
     * @param Table|string $table     A Table object or table name
     * @param string       $fieldName The name of a field
     * @param string       $fieldType The data type of a field
     * @param array        $options   Additional options of a field
     */
    public function addSerializedField($table, $fieldName, $fieldType, array $options = [])
    {
        $options['extend']['is_extend'] = true;
        $options['extend']['is_serialized'] = true;
        $options[ExtendOptionsManager::TYPE_OPTION] = $fieldType;

        if (!isset($options['extend']['owner'])) {
            $options['extend']['owner'] = ExtendScope::OWNER_SYSTEM;
        }
        if (!isset($options[ExtendOptionsManager::MODE_OPTION])) {
            $options[ExtendOptionsManager::MODE_OPTION] = ConfigModel::MODE_READONLY;
        }

        $this->extendOptionsManager->setColumnOptions(
            $this->getTableName($table),
            $fieldName,
            $options
        );
    }

    /**
     * @param Table|string $table A Table object or table name
     *
     * @return string
     */
    protected function getTableName($table)
    {
        return $table instanceof Table ? $table->getName() : $table;
    }
}
