<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Fazland\ElasticaBundle\Persister\ObjectPersisterInterface;
use Fazland\ElasticaBundle\Provider\IndexableInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Automatically update ElasticSearch based on changes to the Doctrine source
 * data. One listener is generated for each Doctrine entity / ElasticSearch type.
 */
class Listener implements EventSubscriber
{
    /**
     * Object persister.
     *
     * @var ObjectPersisterInterface
     */
    protected $objectPersister;

    /**
     * Objects scheduled for insertion.
     *
     * @var \SplObjectStorage
     */
    protected $scheduledForInsertion;

    /**
     * Objects scheduled to be updated or removed.
     *
     * @var \SplObjectStorage
     */
    protected $scheduledForUpdate;

    /**
     * IDs of objects scheduled for removal.
     *
     * @var \SplObjectStorage
     */
    protected $scheduledForDeletion;

    /**
     * PropertyAccessor instance.
     *
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * Configuration for the listener.
     *
     * @var array
     */
    private $config;

    /**
     * @var IndexableInterface
     */
    private $indexable;

    /**
     * Constructor.
     *
     * @param ObjectPersisterInterface $objectPersister
     * @param IndexableInterface       $indexable
     * @param array                    $config
     */
    public function __construct(
        ObjectPersisterInterface $objectPersister,
        IndexableInterface $indexable,
        array $config = []
    ) {
        $this->config = $config;
        $this->indexable = $indexable;
        $this->objectPersister = $objectPersister;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->scheduledForInsertion = new \SplObjectStorage();
        $this->scheduledForUpdate = new \SplObjectStorage();
        $this->scheduledForDeletion = new \SplObjectStorage();
    }

    /**
     * Looks for new objects that should be indexed.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if ($this->objectPersister->handlesObject($entity) && $this->isObjectIndexable($entity)) {
            $this->scheduledForInsertion->attach($entity);
        }
    }

    /**
     * Looks for objects being updated that should be indexed or removed from the index.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if ($this->objectPersister->handlesObject($entity)) {
            if ($this->isObjectIndexable($entity)) {
                $this->scheduledForUpdate->attach($entity);
            } else {
                // Delete if no longer indexable
                $this->scheduleForDeletion($entity);
            }
        }
    }

    /**
     * Delete objects preRemove instead of postRemove so that we have access to the id.  Because this is called
     * preRemove, first check that the entity is managed by Doctrine.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();

        if ($this->objectPersister->handlesObject($entity)) {
            $this->scheduleForDeletion($entity);
        }
    }

    /**
     * Iterating through scheduled actions *after* flushing ensures that the
     * ElasticSearch index will be affected only if the query is successful.
     */
    public function postFlush()
    {
        $this->persistScheduled();
    }

    public function getSubscribedEvents()
    {
        return [
            'postFlush',
        ];
    }

    /**
     * Persist scheduled objects to ElasticSearch
     * After persisting, clear the scheduled queue to prevent multiple data updates when using multiple flush calls.
     */
    private function persistScheduled()
    {
        if ($this->scheduledForInsertion->count()) {
            $this->objectPersister->persist(...$this->scheduledForInsertion);
            $this->scheduledForInsertion = new \SplObjectStorage();
        }

        if ($this->scheduledForUpdate->count()) {
            $this->objectPersister->persist(...$this->scheduledForUpdate);
            $this->scheduledForUpdate = new \SplObjectStorage();
        }

        if ($this->scheduledForDeletion->count()) {
            $this->objectPersister->unpersist(...$this->scheduledForDeletion);
            $this->scheduledForDeletion = new \SplObjectStorage();
        }
    }

    /**
     * Record the specified identifier to delete. Do not need to entire object.
     *
     * @param object $object
     */
    private function scheduleForDeletion($object)
    {
        if ($this->objectPersister->handlesObject($object)) {
            $this->scheduledForDeletion->attach($object);
        }
    }

    /**
     * Checks if the object is indexable or not.
     *
     * @param object $object
     *
     * @return bool
     */
    private function isObjectIndexable($object)
    {
        return $this->indexable->isObjectIndexable(
            $this->config['indexName'],
            $this->config['typeName'],
            $object
        );
    }
}
