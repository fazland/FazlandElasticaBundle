<?php

namespace Fazland\ElasticaBundle\Transformer;

use Elastica\Result;
use Fazland\ElasticaBundle\Elastica\ResultSet;

/**
 * Maps Elastica documents with model objects.
 */
interface ElasticaToModelTransformerInterface
{
    /**
     * Transforms an array of elastica objects into an array of
     * model objects fetched from the doctrine repository.
     *
     * @param Result[]|ResultSet $results array of elastica objects
     *
     * @return object[] array of model objects
     **/
    public function transform($results);
}
