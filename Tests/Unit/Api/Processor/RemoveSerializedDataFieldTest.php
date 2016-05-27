<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\RemoveSerializedDataField;

class RemoveSerializedDataFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testRemoveSerializedDataField($data, $expectedData)
    {
        $context = $this->getContext();
        $context->setResult($data);

        $processor = new RemoveSerializedDataField();
        $processor->process($context);

        $this->assertEquals(
            $expectedData,
            $context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider()
    {
        return [
            'null'                       => [null, null],
            'single, no serialized_data' => [
                [
                    'id'   => 123,
                    'name' => 'test',
                ],
                [
                    'id'   => 123,
                    'name' => 'test',
                ]
            ],
            'single'                     => [
                [
                    'id'              => 123,
                    'name'            => 'test',
                    'serialized_data' => ['field1' => 'value1'],
                ],
                [
                    'id'   => 123,
                    'name' => 'test',
                ]
            ],
            'list'                       => [
                [
                    [
                        'id'              => 123,
                        'name'            => 'test1',
                        'serialized_data' => ['field1' => 'value1'],
                    ],
                    [
                        'id'              => 456,
                        'name'            => 'test2',
                        'serialized_data' => ['field1' => 'value2'],
                    ],
                ],
                [
                    [
                        'id'   => 123,
                        'name' => 'test1',
                    ],
                    [
                        'id'   => 456,
                        'name' => 'test2',
                    ]
                ]
            ],
            'list with relation'         => [
                [
                    [
                        'id'              => 123,
                        'name'            => 'test1',
                        'serialized_data' => ['field1' => 'value1'],
                        'related'         => [
                            'id'              => 100,
                            'serialized_data' => ['field2' => 'value21'],
                        ]
                    ],
                    [
                        'id'              => 456,
                        'name'            => 'test2',
                        'serialized_data' => ['field1' => 'value2'],
                        'related'         => [
                            'id'              => 200,
                            'serialized_data' => ['field2' => 'value22'],
                        ]
                    ],
                ],
                [
                    [
                        'id'      => 123,
                        'name'    => 'test1',
                        'related' => ['id' => 100],
                    ],
                    [
                        'id'      => 456,
                        'name'    => 'test2',
                        'related' => ['id' => 200],
                    ]
                ]
            ],
            'list with relation list'    => [
                [
                    [
                        'id'              => 123,
                        'name'            => 'test1',
                        'serialized_data' => ['field1' => 'value1'],
                        'related'         => [
                            [
                                'id'              => 100,
                                'serialized_data' => ['field2' => 'value21'],
                            ]
                        ]
                    ],
                    [
                        'id'              => 456,
                        'name'            => 'test2',
                        'serialized_data' => ['field1' => 'value2'],
                        'related'         => [
                            [
                                'id'              => 200,
                                'serialized_data' => ['field2' => 'value22'],
                            ]
                        ]
                    ],
                ],
                [
                    [
                        'id'      => 123,
                        'name'    => 'test1',
                        'related' => [
                            ['id' => 100]
                        ],
                    ],
                    [
                        'id'      => 456,
                        'name'    => 'test2',
                        'related' => [
                            ['id' => 200]
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new Context($configProvider, $metadataProvider);
    }
}
