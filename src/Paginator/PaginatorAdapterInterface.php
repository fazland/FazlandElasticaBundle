<?php

namespace Fazland\ElasticaBundle\Paginator;

interface PaginatorAdapterInterface
{
    /**
     * Returns the number of results.
     *
     * @return int the number of results
     */
    public function getTotalHits();

    /**
     * Returns an slice of the results.
     *
     * @param int $offset the offset
     * @param int $length the length
     *
     * @return PartialResultsInterface
     */
    public function getResults($offset, $length);

    /**
     * Returns Aggregations.
     *
     * @return mixed
     */
    public function getAggregations();
}
