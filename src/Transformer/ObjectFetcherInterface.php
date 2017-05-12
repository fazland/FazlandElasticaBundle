<?php

namespace Fazland\ElasticaBundle\Transformer;

interface ObjectFetcherInterface
{
    /**
     * Returns a SORTED list of object given the identifiers.
     * The keys MUST be the object identifier as stored in Elastic document.
     *
     * @param array ...$identifiers
     *
     * @return iterable|object[]
     */
    public function find(...$identifiers);
}
