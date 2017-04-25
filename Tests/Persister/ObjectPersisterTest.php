<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\ObjectPersister;

use Fazland\ElasticaBundle\Elastica\Type;
use Fazland\ElasticaBundle\Persister\ObjectPersister;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

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

class ObjectPersisterTest extends TestCase
{
    public function testThatCanReplaceObject()
    {
        $type = $this->prophesize(Type::class);
        $type->persist(Argument::any())->shouldBeCalledTimes(1);

        $objectPersister = new ObjectPersister($type->reveal(), 'SomeClass');
        $objectPersister->replaceOne(new POPO());
    }

    public function testThatCanInsertObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->persist(Argument::cetera())->shouldBeCalledTimes(1);

        $objectPersister = new ObjectPersister($type->reveal(), 'SomeClass');
        $objectPersister->insertOne(new POPO());
    }

    public function testThatCanDeleteObject()
    {
        $type = $this->prophesize(Type::class);
        $type->unpersist(Argument::cetera())->shouldBeCalledTimes(1);
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();

        $objectPersister = new ObjectPersister($type->reveal(), 'SomeClass');
        $objectPersister->deleteOne(new POPO());
    }

    public function testThatCanInsertManyObjects()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();
        $type->persist(Argument::cetera())->shouldBeCalledTimes(1);

        $objectPersister = new ObjectPersister($type->reveal(), 'SomeClass');
        $objectPersister->insertMany([new POPO(), new POPO()]);
    }
}
