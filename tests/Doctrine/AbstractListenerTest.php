<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Doctrine;

use Fazland\ElasticaBundle\Persister\ObjectPersister;
use Fazland\ElasticaBundle\Provider\IndexableInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * See concrete MongoDB/ORM instances of this abstract test.
 *
 * @author Richard Miller <info@limethinking.co.uk>
 */
abstract class ListenerTest extends TestCase
{
    public function testObjectInsertedOnPersist()
    {
        $entity = new Listener\Entity(1);
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $this->getMockObjectManager()->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity, true);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['indexName' => 'index', 'typeName' => 'type']);
        $listener->postPersist($eventArgs);

        $listener->scheduledForInsertion->rewind();
        $this->assertEquals($entity, $listener->scheduledForInsertion->current());

        $persister->persist(...$listener->scheduledForInsertion)->shouldBeCalled();
        $listener->postFlush($eventArgs);
    }

    public function testNonIndexableObjectNotInsertedOnPersist()
    {
        $entity = new Listener\Entity(1);
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $this->getMockObjectManager()->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity, false);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['indexName' => 'index', 'typeName' => 'type']);
        $listener->postPersist($eventArgs);

        $this->assertEmpty($listener->scheduledForInsertion);

        $persister->persist(Argument::cetera())->shouldNotBeCalled();
        $persister->persist(Argument::cetera())->shouldNotBeCalled();

        $listener->postFlush($eventArgs);
    }

    public function testObjectReplacedOnUpdate()
    {
        $entity = new Listener\Entity(1);
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $this->getMockObjectManager()->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity, true);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['indexName' => 'index', 'typeName' => 'type']);
        $listener->postUpdate($eventArgs);

        $listener->scheduledForUpdate->rewind();
        $this->assertEquals($entity, $listener->scheduledForUpdate->current());

        $persister->persist(...$listener->scheduledForUpdate)->shouldBeCalled();
        $persister->unpersist(Argument::cetera())->shouldNotBeCalled();

        $listener->postFlush($eventArgs);
    }

    public function testNonIndexableObjectRemovedOnUpdate()
    {
        $classMetadata = $this->getMockClassMetadata();
        $objectManager = $this->getMockObjectManager();

        $entity = new Listener\Entity(1);
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $objectManager->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity, false);

        $objectManager->getClassMetadata(get_class($entity))->willReturn($classMetadata);
        $classMetadata->getIdentifierValues($entity)->willReturn(['id' => 1]);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['indexName' => 'index', 'typeName' => 'type']);
        $listener->postUpdate($eventArgs);

        $listener->scheduledForDeletion->rewind();
        $this->assertEmpty($listener->scheduledForUpdate);
        $this->assertEquals($entity, $listener->scheduledForDeletion->current());

        $persister->persist(Argument::cetera())->shouldNotBeCalled();
        $persister->unpersist($entity)->shouldBeCalledTimes(1);

        $listener->postFlush($eventArgs);
    }

    public function testObjectDeletedOnRemove()
    {
        $classMetadata = $this->getMockClassMetadata();
        $objectManager = $this->getMockObjectManager();

        $entity = new Listener\Entity(1);
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $objectManager->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity);

        $objectManager->getClassMetadata(get_class($entity))->willReturn($classMetadata);
        $classMetadata->getIdentifierValues($entity)->willReturn(['id' => 1]);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['indexName' => 'index', 'typeName' => 'type']);
        $listener->preRemove($eventArgs);

        $listener->scheduledForDeletion->rewind();
        $this->assertEquals($entity, $listener->scheduledForDeletion->current());

        $persister->unpersist($entity)->shouldBeCalledTimes(1);
        $listener->postFlush($eventArgs);
    }

    public function testObjectWithNonStandardIdentifierDeletedOnRemove()
    {
        $classMetadata = $this->getMockClassMetadata();
        $objectManager = $this->getMockObjectManager();

        $entity = new Listener\Entity(1);
        $entity->identifier = 'foo';
        $persister = $this->getMockPersister($entity);
        $eventArgs = $this->createLifecycleEventArgs($entity, $objectManager->reveal());
        $indexable = $this->getMockIndexable('index', 'type', $entity);

        $objectManager->getClassMetadata(get_class($entity))->willReturn($classMetadata);
        $classMetadata->getIdentifierValues($entity)->willReturn(['id' => 1]);

        $listener = $this->createListener($persister->reveal(), $indexable->reveal(), ['identifier' => 'identifier', 'indexName' => 'index', 'typeName' => 'type']);
        $listener->preRemove($eventArgs);

        $listener->scheduledForDeletion->rewind();
        $this->assertEquals($entity, $listener->scheduledForDeletion->current());

        $persister->unpersist($entity)->shouldBeCalledTimes(1);
        $listener->postFlush($eventArgs);
    }

    abstract protected function getLifecycleEventArgsClass();

    abstract protected function getListenerClass();

    /**
     * @return string
     */
    abstract protected function getObjectManagerClass();

    /**
     * @return string
     */
    abstract protected function getClassMetadataClass();

    private function createLifecycleEventArgs(...$args)
    {
        $class = $this->getLifecycleEventArgsClass();

        return new $class(...$args);
    }

    private function createListener(...$args)
    {
        $class = $this->getListenerClass();

        return new $class(...$args);
    }

    private function getMockClassMetadata(): ObjectProphecy
    {
        return $this->prophesize($this->getClassMetadataClass());
    }

    private function getMockObjectManager(): ObjectProphecy
    {
        return $this->prophesize($this->getObjectManagerClass());
    }

    private function getMockPersister($object): ObjectProphecy
    {
        $persister = $this->prophesize(ObjectPersister::class);
        $persister->handlesObject($object)->willReturn(true);

        return $persister;
    }

    private function getMockIndexable(string $indexName, string $typeName, $object, bool $return = null): ObjectProphecy
    {
        $indexable = $this->prophesize(IndexableInterface::class);
        $method = $indexable->isObjectIndexable($indexName, $typeName, $object);

        if (null !== $return) {
            $method->willReturn($return)->shouldBeCalledTimes(1);
        } else {
            $method->shouldNotBeCalled();
        }

        return $indexable;
    }
}

namespace Fazland\ElasticaBundle\Tests\Doctrine\Listener;

class Entity
{
    private $id;
    public $identifier;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
