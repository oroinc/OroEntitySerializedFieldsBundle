<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Remove 'serialized_data' field from the result.
 */
class RemoveSerializedDataField implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasResult()) {
            // no result
            return;
        }

        $data = $context->getResult();
        if (empty($data) || !is_array($data)) {
            // empty or not supported result
            return;
        }

        $this->removeSerializedDataField($data);
        $context->setResult($data);
    }

    /**
     * @param array $data
     */
    protected function removeSerializedDataField(array &$data)
    {
        $isSerializedDataFound = false;
        foreach ($data as $key => &$value) {
            if ('serialized_data' === $key) {
                $isSerializedDataFound = true;
            } elseif (is_array($value)) {
                $this->removeSerializedDataField($value);
            }
        }
        unset($value);
        if ($isSerializedDataFound) {
            unset($data['serialized_data']);
        }
    }
}
