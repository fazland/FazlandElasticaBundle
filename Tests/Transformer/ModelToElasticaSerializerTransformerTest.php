<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Transformer\ModelToElasticaIdentifierTransformer;

use Elastica\Document;
use Fazland\ElasticaBundle\Elastica\Type;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaSerializerTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\PropertyAccess;

class POPO
{
    protected $id = 123;
    protected $name = 'Name';

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

class ModelToElasticaSerializerTransformerTest extends TestCase
{
    /**
     * @var Type|ObjectProphecy
     */
    private $type;

    /**
     * @var ModelToElasticaSerializerTransformer
     */
    private $transformer;

    protected function setUp()
    {
        $this->type = $this->prophesize(Type::class);

        $transformer = new ModelToElasticaSerializerTransformer(['identifier' => 'id']);
        $transformer->setSerializerCallback(function () {
            return [];
        });
        $transformer->setType($this->type->reveal());
        $transformer->setPropertyAccessor(PropertyAccess::createPropertyAccessor());

        $this->transformer = $transformer;
    }

    public function testGetDocumentWithIdentifierOnly()
    {
        $document = $this->transformer->transform(new POPO(), []);
        $data = $document->getData();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals(123, $document->getId());
        $this->assertCount(0, $data);
    }

    public function testGetDocumentWithIdentifierOnlyWithFields()
    {
        $document = $this->transformer->transform(new POPO(), ['name' => []]);
        $data = $document->getData();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals(123, $document->getId());
        $this->assertCount(0, $data);
    }
}
