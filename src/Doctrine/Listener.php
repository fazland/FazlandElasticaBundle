<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
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
     * Configuration for the listener.
     *
     * @var array
     */
    private $config;

    /**
     * Objects scheduled for insertion.
     *
     * @var array
     */
    public $scheduledForInsertion = [];

    /**
     * Objects scheduled to be updated or removed.
     *
     * @var array
     */
    public $scheduledForUpdate = [];

    /**
     * IDs of objects scheduled for removal.
     *
     * @var array
     */
    public $scheduledForDeletion = [];

    /**
     * PropertyAccessor instance.
     *
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

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
            $this->scheduledForInsertion[] = $entity;
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
                $this->scheduledForUpdate[] = $entity;
            } else {
                // Delete if no longer indexable
                $this->scheduleForDeletion($entity, $eventArgs->getObjectManager());
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
            $this->scheduleForDeletion($entity, $eventArgs->getObjectManager());
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
        if (count($this->scheduledForInsertion)) {
            $this->objectPersister->persist(...$this->scheduledForInsertion);
            $this->scheduledForInsertion = [];
        }

        if (count($this->scheduledForUpdate)) {
            $this->objectPersister->persist(...$this->scheduledForUpdate);
            $this->scheduledForUpdate = [];
        }

        if (count($this->scheduledForDeletion)) {
            $this->objectPersister->deleteById(...$this->scheduledForDeletion);
            $this->scheduledForDeletion = [];
        }
    }

    /**
     * Record the specified identifier to delete. Do not need to entire object.
     *
     * @param object        $object
     * @param ObjectManager $om
     */
    private function scheduleForDeletion($object, ObjectManager $om)
    {
        if (! isset($this->config['identifier'])) {
            $metadata = $om->getClassMetadata(ClassUtils::getClass($object));
            $identifier = $metadata->getIdentifierValues($object);
        } else {
            $identifierFields = (array) $this->config['identifier'];
            $identifier = [];

            foreach ($identifierFields as $field) {
                $identifier[] = $this->propertyAccessor->getValue($object, $field);
            }
        }

        $this->scheduledForDeletion[] = implode(' ', $identifier);
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
