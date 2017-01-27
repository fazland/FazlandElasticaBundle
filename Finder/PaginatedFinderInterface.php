<?php

namespace Fazland\ElasticaBundle\Finder;

use Elastica\Query;
use Fazland\ElasticaBundle\Paginator\PaginatorAdapterInterface;
use Pagerfanta\Pagerfanta;

interface PaginatedFinderInterface extends FinderInterface
{
    /**
     * Searches for query results and returns them wrapped in a paginator.
     *
     * @param mixed $query   Can be a string, an array or an \Elastica\Query object
     * @param array $options
     *
     * @return Pagerfanta paginated results
     */
    public function findPaginated($query, $options = []);

    /**
     * Creates a paginator adapter for this query.
     *
     * @param mixed $query
     * @param array $options
     *
     * @return PaginatorAdapterInterface
     */
    public function createPaginatorAdapter($query, $options = []);

    /**
     * Creates a hybrid paginator adapter for this query.
     *
     * @param mixed $query
     *
     * @return PaginatorAdapterInterface
     */
    public function createHybridPaginatorAdapter($query);
}
