<?php

namespace Fazland\ElasticaBundle\Paginator;

use Pagerfanta\Adapter\AdapterInterface;

class FantaPaginatorAdapter implements AdapterInterface
{
    private $adapter;

    /**
     * @param \Fazland\ElasticaBundle\Paginator\PaginatorAdapterInterface $adapter
     */
    public function __construct(PaginatorAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns the number of results.
     *
     * @return int the number of results
     */
    public function getNbResults()
    {
        return $this->adapter->getTotalHits();
    }

    /**
     * Returns Aggregations.
     *
     * @return mixed
     *
     * @api
     */
    public function getAggregations()
    {
        return $this->adapter->getAggregations();
    }

    /**
     * Returns a slice of the results.
     *
     * @param int $offset the offset
     * @param int $length the length
     *
     * @return array|\Traversable the slice
     */
    public function getSlice($offset, $length)
    {
        return $this->adapter->getResults($offset, $length)->toArray();
    }
}
