- [EntitySerializedFieldsBundle](#entityserializedfieldsbundle)

EntitySerializedFieldsBundle
----------------------------
* The following methods in class `EntityConfigListener`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/EventListener/EntityConfigListener.php#L78 "Oro\Bundle\EntitySerializedFieldsBundle\EventListener\EntityConfigListener")</sup> were removed:
   - `initializeEntity`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/EventListener/EntityConfigListener.php#L78 "Oro\Bundle\EntitySerializedFieldsBundle\EventListener\EntityConfigListener::initializeEntity")</sup>
   - `getEntityConfig`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/EventListener/EntityConfigListener.php#L203 "Oro\Bundle\EntitySerializedFieldsBundle\EventListener\EntityConfigListener::getEntityConfig")</sup>
   - `revertEntityState`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/EventListener/EntityConfigListener.php#L214 "Oro\Bundle\EntitySerializedFieldsBundle\EventListener\EntityConfigListener::revertEntityState")</sup>
* The `AddSerializedFields::__construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider)`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/Api/Processor/Config/AddSerializedFields.php#L31 "Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\AddSerializedFields")</sup> method was changed to `AddSerializedFields::__construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.1.0/Api/Processor/Config/AddSerializedFields.php#L31 "Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\AddSerializedFields")</sup>
* The `AddSerializedFields::$extendConfigProvider`<sup>[[?]](https://github.com/oroinc/OroEntitySerializedFieldsBundle/tree/3.0.0/Api/Processor/Config/AddSerializedFields.php#L25 "Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\AddSerializedFields::$extendConfigProvider")</sup> property was removed.

