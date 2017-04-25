<?php

namespace Fazland\ElasticaBundle\Paginator;

use Elastica\ResultSet;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;

/**
 * Partial transformed result set.
 */
class TransformedPartialResults extends RawPartialResults
{
    protected $transformer;

    /**
     * @param ResultSet                                                               $resultSet
     * @param \Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface $transformer
     */
    public function __construct(ResultSet $resultSet, ElasticaToModelTransformerInterface $transformer)
    {
        parent::__construct($resultSet);

        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->transformer->transform($this->resultSet->getResults());
    }
}
