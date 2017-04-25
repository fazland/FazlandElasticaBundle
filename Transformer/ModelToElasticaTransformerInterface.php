<?php

namespace Fazland\ElasticaBundle\Transformer;

/**
 * Maps Elastica documents with model objects.
 */
interface ModelToElasticaTransformerInterface
{
    /**
     * Transforms an object into an elastica object having the required keys.
     *
     * @param object $object  the object to convert
     * @param array  $mapping the mapping for this type
     *
     * @return \Elastica\Document
     **/
    public function transform($object, array $mapping);
}
