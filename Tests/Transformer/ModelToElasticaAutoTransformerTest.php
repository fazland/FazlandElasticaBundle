<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Transformer\ModelToElasticaAutoTransformer;

use Elastica\Document;
use Elastica\Type;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\TransformEvent;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaAutoTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class POPO
{
    public $id = 123;
    public $name = 'someName';
    private $desc = 'desc';
    public $float = 7.2;
    public $bool = true;
    public $falseBool = false;
    public $date;
    public $nullValue;
    public $file;
    public $fileContents;

    public function __construct()
    {
        $this->date         = new \DateTime('1979-05-05');
        $this->file         = new \SplFileInfo(__DIR__.'/../fixtures/attachment.odt');
        $this->fileContents = file_get_contents(__DIR__.'/../fixtures/attachment.odt');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIterator()
    {
        $iterator = new \ArrayIterator();
        $iterator->append('value1');

        return $iterator;
    }

    public function getArray()
    {
        return [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
    }

    public function getMultiArray()
    {
        return [
            'key1'  => 'value1',
            'key2'  => ['value2', false, 123, 8.9, new \DateTime('1978-09-07')],
        ];
    }

    public function getBool()
    {
        return $this->bool;
    }

    public function getFalseBool()
    {
        return $this->falseBool;
    }

    public function getFloat()
    {
        return $this->float;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getNullValue()
    {
        return $this->nullValue;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getFileContents()
    {
        return $this->fileContents;
    }

    public function getSub()
    {
        return [
            (object) ['foo' => 'foo', 'bar' => 'foo', 'id' => 1],
            (object) ['foo' => 'bar', 'bar' => 'bar', 'id' => 2],
        ];
    }

    public function getObj()
    {
        return ['foo' => 'foo', 'bar' => 'foo', 'id' => 1];
    }

    public function getNestedObject()
    {
        return ['key1' => (object) ['id' => 1, 'key1sub1' => 'value1sub1', 'key1sub2' => 'value1sub2']];
    }

    public function getUpper()
    {
        return (object) ['id' => 'parent', 'name' => 'a random name'];
    }

    public function getUpperAlias()
    {
        return $this->getUpper();
    }

    public function getObjWithoutIdentifier()
    {
        return (object) ['foo' => 'foo', 'bar' => 'foo'];
    }

    public function getSubWithoutIdentifier()
    {
        return [
            (object) ['foo' => 'foo', 'bar' => 'foo'],
            (object) ['foo' => 'bar', 'bar' => 'bar'],
        ];
    }
}

class CastableObject
{
    public $foo;

    public function __toString()
    {
        return $this->foo;
    }
}

class ModelToElasticaAutoTransformerTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $dispatcher;

    /**
     * @var Type|ObjectProphecy
     */
    private $type;

    /**
     * @var ModelToElasticaAutoTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->type = $this->prophesize(Type::class);

        $this->transformer = new ModelToElasticaAutoTransformer(['identifier' => 'id'], $this->dispatcher->reveal());
        $this->transformer->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
        $this->transformer->setType($this->type->reveal());
    }

    public function testTransformerDispatches()
    {
        $this->dispatcher->dispatch(Events::PRE_TRANSFORM, Argument::that(function ($arg) {
            return $arg instanceof TransformEvent && null !== $arg->getType();
        }))->shouldBeCalled();
        $this->dispatcher->dispatch(Events::POST_TRANSFORM, Argument::that(function ($arg) {
            return $arg instanceof TransformEvent && null !== $arg->getType();
        }))->shouldBeCalled();

        $this->transformer->transform(new POPO(), []);
    }

    public function testPropertyPath()
    {
        $document = $this->transformer->transform(new POPO(), [
            'properties' => ['name' => ['property_path' => false]]
        ]);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertFalse($document->has('name'));

        $document = $this->transformer->transform(new POPO(), [
            'properties' => ['realName' => ['property_path' => 'name']]
        ]);
        $this->assertInstanceOf(Document::class, $document);
        $this->assertTrue($document->has('realName'));
        $this->assertEquals('someName', $document->get('realName'));
    }

    public function testThatCanTransformObject()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['name' => []]]);
        $data        = $document->getData();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals(123, $document->getId());
        $this->assertEquals('someName', $data['name']);
    }

    public function testThatCanTransformObjectWithCorrectTypes()
    {
        $document    = $this->transformer->transform(
            new POPO(), [
            'properties' => [
                'name'      => [],
                'float'     => [],
                'bool'      => [],
                'date'      => [],
                'falseBool' => [],
            ]
        ]);
        $data        = $document->getData();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals(123, $document->getId());
        $this->assertEquals('someName', $data['name']);
        $this->assertEquals(7.2, $data['float']);
        $this->assertEquals(true, $data['bool']);
        $this->assertEquals(false, $data['falseBool']);
        $expectedDate = new \DateTime('1979-05-05');
        $this->assertEquals($expectedDate->format('c'), $data['date']);
    }

    public function testThatCanTransformObjectWithIteratorValue()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['iterator' => []]]);
        $data        = $document->getData();

        $this->assertEquals(['value1'], $data['iterator']);
    }

    public function testThatCanTransformObjectWithArrayValue()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['array' => []]]);
        $data        = $document->getData();

        $this->assertEquals(
            [
                 'key1'  => 'value1',
                 'key2'  => 'value2',
            ], $data['array']
        );
    }

    public function testThatCanTransformObjectWithMultiDimensionalArrayValue()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['multiArray' => []]]);
        $data        = $document->getData();

        $expectedDate = new \DateTime('1978-09-07');

        $this->assertEquals(
            [
                 'key1'  => 'value1',
                 'key2'  => ['value2', false, 123, 8.9, $expectedDate->format('c')],
            ], $data['multiArray']
        );
    }

    public function testThatNullValuesAreNotFilteredOut()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['nullValue' => []]]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('nullValue', $data));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\RuntimeException
     */
    public function testThatCannotTransformObjectWhenGetterDoesNotExistForPrivateMethod()
    {
        $this->transformer->transform(new POPO(), ['properties' => ['desc' => []]]);
    }

    public function testFileAddedForAttachmentMapping()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['file' => ['type' => 'attachment']]]);
        $data        = $document->getData();

        $this->assertEquals(base64_encode(file_get_contents(__DIR__.'/../fixtures/attachment.odt')), $data['file']);
    }

    public function testFileContentsAddedForAttachmentMapping()
    {
        $document    = $this->transformer->transform(new POPO(), ['properties' => ['fileContents' => ['type' => 'attachment']]]);
        $data        = $document->getData();

        $this->assertEquals(
            base64_encode(file_get_contents(__DIR__.'/../fixtures/attachment.odt')), $data['fileContents']
        );
    }

    public function testNestedMapping()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'sub' => [
                    'type' => 'nested',
                    'properties' => ['foo' => []],
                ],
            ],
        ]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('sub', $data));
        $this->assertInternalType('array', $data['sub']);
        $this->assertEquals([
             ['foo' => 'foo'],
             ['foo' => 'bar'],
           ], $data['sub']);
    }

    public function tesObjectMapping()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'sub' => [
                    'type' => 'object',
                    'properties' => ['bar'],
                ],
            ],
        ]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('sub', $data));
        $this->assertInternalType('array', $data['sub']);
        $this->assertEquals([
             ['bar' => 'foo'],
             ['bar' => 'bar'],
           ], $data['sub']);
    }

    public function testObjectDoesNotRequireProperties()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'obj' => [
                    'type' => 'object',
                ],
            ]
        ]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('obj', $data));
        $this->assertInternalType('array', $data['obj']);
        $this->assertEquals([
             'foo' => 'foo',
             'bar' => 'foo',
             'id' => 1,
       ], $data['obj']);
    }

    public function testObjectsMappingOfAtLeastOneAutoMappedObjectAndAtLeastOneManuallyMappedObject()
    {
        $document    = $this->transformer->transform(
            new POPO(),
            [
                'properties' => [
                    'obj'          => ['type' => 'object', 'properties' => []],
                    'nestedObject' => [
                        'type'       => 'object',
                        'properties' => [
                            'key1sub1' => [
                                'type'       => 'string',
                                'properties' => [],
                            ],
                            'key1sub2' => [
                                'type'       => 'string',
                                'properties' => [],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('obj', $data));
        $this->assertTrue(array_key_exists('nestedObject', $data));
        $this->assertInternalType('array', $data['obj']);
        $this->assertInternalType('array', $data['nestedObject']);
        $this->assertEquals(
            [
                'foo' => 'foo',
                'bar' => 'foo',
                'id'  => 1,
            ],
            $data['obj']
        );
        $this->assertEquals(
            [
                'key1sub1' => 'value1sub1',
                'key1sub2' => 'value1sub2',
            ],
            $data['nestedObject'][0]
        );
    }

    public function testParentMapping()
    {
        $document    = $this->transformer->transform(new POPO(), [
            '_parent' => ['type' => 'upper', 'property' => 'upper', 'identifier' => 'id'],
        ]);

        $this->assertEquals('parent', $document->getParent());
    }

    public function testParentMappingWithCustomIdentifier()
    {
        $document    = $this->transformer->transform(new POPO(), [
            '_parent' => ['type' => 'upper', 'property' => 'upper', 'identifier' => 'name'],
        ]);

        $this->assertEquals('a random name', $document->getParent());
    }

    public function testParentMappingWithNullProperty()
    {
        $document    = $this->transformer->transform(new POPO(), [
            '_parent' => ['type' => 'upper', 'property' => null, 'identifier' => 'id'],
        ]);

        $this->assertEquals('parent', $document->getParent());
    }

    public function testParentMappingWithCustomProperty()
    {
        $document    = $this->transformer->transform(new POPO(), [
            '_parent' => ['type' => 'upper', 'property' => 'upperAlias', 'identifier' => 'id'],
        ]);

        $this->assertEquals('parent', $document->getParent());
    }

    public function testThatMappedObjectsDontNeedAnIdentifierField()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'objWithoutIdentifier' => [
                    'type' => 'object',
                    'properties' => [
                        'foo' => [],
                        'bar' => []
                    ]
                ],
            ],
        ]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('objWithoutIdentifier', $data));
        $this->assertInternalType('array', $data['objWithoutIdentifier']);
        $this->assertEquals([
            'foo' => 'foo',
            'bar' => 'foo'
        ], $data['objWithoutIdentifier']);
    }

    public function testThatNestedObjectsDontNeedAnIdentifierField()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'subWithoutIdentifier' => [
                    'type' => 'nested',
                    'properties' => [
                        'foo' => [],
                        'bar' => []
                    ],
                ],
            ],
        ]);
        $data        = $document->getData();

        $this->assertTrue(array_key_exists('subWithoutIdentifier', $data));
        $this->assertInternalType('array', $data['subWithoutIdentifier']);
        $this->assertEquals([
            ['foo' => 'foo', 'bar' => 'foo'],
            ['foo' => 'bar', 'bar' => 'bar'],
        ], $data['subWithoutIdentifier']);
    }

    public function testNestedTransformHandlesSingleObjects()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'upper' => [
                    'type' => 'nested',
                    'properties' => ['name' => null]
                ],
            ],
        ]);

        $data = $document->getData();
        $this->assertEquals('a random name', $data['upper']['name']);
    }

    public function testNestedTransformReturnsAnEmptyArrayForNullValues()
    {
        $document    = $this->transformer->transform(new POPO(), [
            'properties' => [
                'nullValue' => [
                    'type' => 'nested',
                    'properties' => [
                        'foo' => [],
                        'bar' => []
                    ],
                ],
            ],
        ]);

        $data = $document->getData();
        $this->assertInternalType('array', $data['nullValue']);
        $this->assertEmpty($data['nullValue']);
    }

    public function testUnmappedFieldValuesAreNormalisedToStrings()
    {
        $object = new \stdClass();
        $value = new CastableObject();
        $value->foo = 'bar';

        $object->id = 123;
        $object->unmappedValue = $value;

        $document    = $this->transformer->transform($object, [
            'properties' => [
                'unmappedValue' => ['property' => 'unmappedValue']
            ],
        ]);

        $data = $document->getData();
        $this->assertEquals('bar', $data['unmappedValue']);
    }
}
