<?php

namespace Fazland\ElasticaBundle\Tests\ObjectPersister;

use Elastica\Type;
use Fazland\ElasticaBundle\Persister\ObjectPersister;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaAutoTransformer;
use Symfony\Component\PropertyAccess\PropertyAccess;

class POPO
{
    public $id   = 123;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return 'popoName';
    }
}

class InvalidObjectPersister extends ObjectPersister
{
    public function transformToElasticaDocument($object)
    {
        throw new \BadMethodCallException('Invalid transformation');
    }
}

class ObjectPersisterTest extends \PHPUnit_Framework_TestCase
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
        $fields = array('name' => array());

        $objectPersister = new ObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
        $objectPersister->replaceOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotReplaceObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->never())
            ->method('addDocument');

        $transformer = $this->getTransformer($typeMock);
        $fields = array('name' => array());

        $objectPersister = new InvalidObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
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
        $fields = array('name' => array());

        $objectPersister = new ObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
        $objectPersister->insertOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotInsertObject()
    {

        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->never())
            ->method('addDocument');

        $transformer = $this->getTransformer($typeMock);
        $fields = array('name' => array());

        $objectPersister = new InvalidObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
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
        $fields = array('name' => array());

        $objectPersister = new ObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
        $objectPersister->deleteOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotDeleteObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->never())
            ->method('addDocument');

        $transformer = $this->getTransformer($typeMock);
        $fields = array('name' => array());

        $objectPersister = new InvalidObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
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
            ->method('addDocument');
        $typeMock->expects($this->once())
            ->method('addDocuments');

        $transformer = $this->getTransformer($typeMock);
        $fields = array('name' => array());

        $objectPersister = new ObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
        $objectPersister->insertMany(array(new POPO(), new POPO()));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotInsertManyObject()
    {
        /** @var $typeMock \PHPUnit_Framework_MockObject_MockObject|\Elastica\Type */
        $typeMock = $this->getMockBuilder('Elastica\Type')
            ->disableOriginalConstructor()
            ->getMock();
        $typeMock->expects($this->never())
            ->method('deleteById');
        $typeMock->expects($this->never())
            ->method('addDocument');
        $typeMock->expects($this->never())
            ->method('addDocuments');

        $transformer = $this->getTransformer($typeMock);

        $fields = array('name' => array());

        $objectPersister = new InvalidObjectPersister($typeMock, $transformer, 'SomeClass', $fields);
        $objectPersister->insertMany(array(new POPO(), new POPO()));
    }

    /**
     * @return ModelToElasticaAutoTransformer
     */
    private function getTransformer(Type $type)
    {
        $transformer = new ModelToElasticaAutoTransformer($type);
        $transformer->setPropertyAccessor(PropertyAccess::createPropertyAccessor());

        return $transformer;
    }
}
