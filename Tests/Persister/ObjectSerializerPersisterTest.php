<?php

namespace Fazland\ElasticaBundle\Tests\ObjectSerializerPersister;

use Elastica\Type;
use Fazland\ElasticaBundle\Persister\ObjectSerializerPersister;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaIdentifierTransformer;
use Symfony\Component\PropertyAccess\PropertyAccess;

class POPO
{
    public $id   = 123;
    public $name = 'popoName';

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

class ObjectSerializerPersisterTest extends \PHPUnit_Framework_TestCase
{
    public function testThatCanReplaceObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->once())
            ->method('updateDocuments');

        $transformer = $this->getTransformer($typeMock);
        $serializerMock = $this->getMockBuilder('Fazland\ElasticaBundle\Serializer\Callback')->getMock();
        $serializerMock->expects($this->once())->method('serialize');

        $objectPersister = new ObjectSerializerPersister($typeMock, $transformer, 'SomeClass', [$serializerMock, 'serialize']);
        $objectPersister->replaceOne(new POPO());
    }

    public function testThatCanInsertObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->once())
            ->method('addDocuments');

        $transformer = $this->getTransformer($typeMock);
        $serializerMock = $this->getMockBuilder('Fazland\ElasticaBundle\Serializer\Callback')->getMock();
        $serializerMock->expects($this->once())->method('serialize');

        $objectPersister = new ObjectSerializerPersister($typeMock, $transformer, 'SomeClass', [$serializerMock, 'serialize']);
        $objectPersister->insertOne(new POPO());
    }

    public function testThatCanDeleteObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->once())
            ->method('deleteDocuments');
        $typeMock->expects($this->never())
            ->method('addDocument');

        $transformer = $this->getTransformer($typeMock);
        $serializerMock = $this->getMockBuilder('Fazland\ElasticaBundle\Serializer\Callback')->getMock();
        $serializerMock->expects($this->once())->method('serialize');

        $objectPersister = new ObjectSerializerPersister($typeMock, $transformer, 'SomeClass', [$serializerMock, 'serialize']);
        $objectPersister->deleteOne(new POPO());
    }

    public function testThatCanInsertManyObjects()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->never())
            ->method('addObject');
        $typeMock->expects($this->never())
            ->method('addObjects');
        $typeMock->expects($this->once())
            ->method('addDocuments');

        $transformer = $this->getTransformer($typeMock);
        $serializerMock = $this->getMockBuilder('Fazland\ElasticaBundle\Serializer\Callback')->getMock();
        $serializerMock->expects($this->exactly(2))->method('serialize');

        $objectPersister = new ObjectSerializerPersister($typeMock, $transformer, 'SomeClass', [$serializerMock, 'serialize']);
        $objectPersister->insertMany([new POPO(), new POPO()]);
    }

    /**
     * @param Type $type
     *
     * @return ModelToElasticaIdentifierTransformer
     */
    private function getTransformer(Type $type)
    {
        $transformer = new ModelToElasticaIdentifierTransformer($type, ['identifier' => 'id']);
        $transformer->setPropertyAccessor(PropertyAccess::createPropertyAccessor());

        return $transformer;
    }
}
