<?php

namespace Fazland\ElasticaBundle\Paginator;

interface PartialResultsInterface
{
    /**
     * Returns the paginated results.
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns the number of results.
     *
     * @return int the number of results
     */
    public function getTotalHits();

    /**
     * Returns the aggregations.
     *
     * @return array
     */
    public function getAggregations();
}
