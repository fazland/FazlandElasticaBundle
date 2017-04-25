<?php

namespace Fazland\ElasticaBundle\Persister;

use Fazland\ElasticaBundle\Elastica\Type;

/**
 * Inserts, replaces and deletes single documents in an elastica type
 * Accepts domain model objects and converts them to elastica documents.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 *
 * @deprecated Object persisters are deprecated. Please use Type methods directly.
 */
class ObjectPersister implements ObjectPersisterInterface
{
    protected $type;
    protected $objectClass;
    protected $logger;

    /**
     * @param Type                                $type
     * @param string                              $objectClass
     */
    public function __construct(Type $type, $objectClass)
    {
        $this->type            = $type;
        $this->objectClass     = $objectClass;
    }

    /**
     * {@inheritdoc}
     */
    public function handlesObject($object)
    {
        return $object instanceof $this->objectClass;
    }

    /**
     * {@inheritdoc}
     */
    public function insertOne($object)
    {
        $this->persist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceOne($object)
    {
        $this->persist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne($object)
    {
        $this->unpersist($object);
    }

    /**
     * {@inheritdoc}
     */
    public function insertMany(array $objects)
    {
        $this->persist(...$objects);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceMany(array $objects)
    {
        $this->persist(...$objects);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $objects)
    {
        $this->unpersist(...$objects);
    }

    /**
     * @inheritDoc
     */
    public function deleteManyByIdentifiers(array $identifiers)
    {
        $this->deleteById(...$identifiers);
    }

    /**
     * @inheritDoc
     */
    public function persist(...$objects)
    {
        $this->type->persist(...$objects);
    }

    /**
     * @inheritDoc
     */
    public function unpersist(...$objects)
    {
        $this->type->unpersist(...$objects);
    }

    /**
     * @inheritDoc
     */
    public function deleteById(...$identifiers)
    {
        $this->type->deleteIds($identifiers);
    }
}
