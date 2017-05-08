<?php

namespace Fazland\ElasticaBundle\Persister;

/**
 * Inserts, replaces and deletes single documents in an elastica type
 * Accepts domain model objects and converts them to elastica documents.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ObjectPersisterInterface
{
    /**
     * Checks if this persister can handle the given object or not.
     *
     * @param mixed $object
     *
     * @return bool
     */
    public function handlesObject($object);

    /**
     * Insert one object into the type
     * The object will be transformed to an elastica document.
     *
     * @param object $object
     *
     * @deprecated This method is deprecated. Use $this->persist instead.
     */
    public function insertOne($object);

    /**
     * Replaces one object in the type.
     *
     * @param object $object
     *
     * @deprecated This method is deprecated. Use $this->persist instead.
     **/
    public function replaceOne($object);

    /**
     * Deletes one object in the type.
     *
     * @param object $object
     *
     * @deprecated This method is deprecated. Use $this->unpersist instead.
     **/
    public function deleteOne($object);

    /**
     * Bulk inserts an array of objects in the type.
     *
     * @param array $objects array of domain model objects
     *
     * @deprecated This method is deprecated. Use $this->persist instead.
     */
    public function insertMany(array $objects);

    /**
     * Bulk updates an array of objects in the type.
     *
     * @param array $objects array of domain model objects
     *
     * @deprecated This method is deprecated. Use $this->persist instead.
     */
    public function replaceMany(array $objects);

    /**
     * Bulk deletes an array of objects in the type.
     *
     * @param array $objects array of domain model objects
     *
     * @deprecated This method is deprecated. Use $this->unpersist instead.
     */
    public function deleteMany(array $objects);

    /**
     * Bulk deletes records from an array of identifiers.
     *
     * @param array $identifiers array of domain model object identifiers
     *
     * @deprecated This method is deprecated. Use $this->deleteById instead.
     */
    public function deleteManyByIdentifiers(array $identifiers);

    /**
     * Bulk persists objects in the type.
     *
     * @param array ...$objects
     */
    public function persist(...$objects);

    /**
     * Bulk deletes objects in the type.
     *
     * @param array ...$objects
     */
    public function unpersist(...$objects);

    /**
     * Deletes one object in the type by id.
     *
     * @param array $identifiers
     */
    public function deleteById(...$identifiers);
}
