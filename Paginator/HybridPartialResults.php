<?php

namespace Fazland\ElasticaBundle\Paginator;

use Elastica\ResultSet;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;

/**
 * Partial transformed result set
 */
class HybridPartialResults extends RawPartialResults
{
    /**
     * @var ElasticaToModelTransformerInterface
     */
    protected $transformer;

    /**
     * @param ResultSet                           $resultSet
     * @param ElasticaToModelTransformerInterface $transformer
     */
    public function __construct(ResultSet $resultSet, ElasticaToModelTransformerInterface $transformer)
    {
        parent::__construct($resultSet);

        $this->transformer = $transformer;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->transformer->hybridTransform($this->resultSet->getResults());
    }
}
