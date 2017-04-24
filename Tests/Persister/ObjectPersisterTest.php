<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\ObjectPersister;

use Elastica\Type;
use Fazland\ElasticaBundle\Persister\ObjectPersister;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaAutoTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

class ObjectPersisterTest extends TestCase
{
    public function testThatCanReplaceObject()
    {
        $type = $this->prophesize(Type::class);
        $type->updateDocuments(Argument::any())->shouldBeCalledTimes(1);

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new ObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->replaceOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotReplaceObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new InvalidObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->replaceOne(new POPO());
    }

    public function testThatCanInsertObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocuments(Argument::cetera())->shouldBeCalledTimes(1);

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new ObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->insertOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotInsertObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new InvalidObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->insertOne(new POPO());
    }

    public function testThatCanDeleteObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteDocuments(Argument::cetera())->shouldBeCalledTimes(1);
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new ObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->deleteOne(new POPO());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotDeleteObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new InvalidObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->deleteOne(new POPO());
    }

    public function testThatCanInsertManyObjects()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();
        $type->addDocuments(Argument::cetera())->shouldBeCalledTimes(1);

        $transformer = $this->getTransformer($type);
        $fields = ['name' => []];

        $objectPersister = new ObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->insertMany([new POPO(), new POPO()]);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testThatErrorIsHandledWhenCannotInsertManyObject()
    {
        $type = $this->prophesize(Type::class);
        $type->deleteById(Argument::cetera())->shouldNotBeCalled();
        $type->addDocument(Argument::cetera())->shouldNotBeCalled();
        $type->addDocuments(Argument::cetera())->shouldNotBeCalled();

        $transformer = $this->getTransformer($type);

        $fields = ['name' => []];

        $objectPersister = new InvalidObjectPersister($type->reveal(), $transformer, 'SomeClass', $fields);
        $objectPersister->insertMany([new POPO(), new POPO()]);
    }

    private function getTransformer($type)
    {
        $transformer = new ModelToElasticaAutoTransformer($type->reveal(), ['identifier' => 'id']);
        $transformer->setPropertyAccessor(PropertyAccess::createPropertyAccessor());

        return $transformer;
    }
}
