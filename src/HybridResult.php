<?php

namespace Fazland\ElasticaBundle;

use Elastica\Result;

@trigger_error('Hybrid results have been deprecated. Please use the bundle\'s ResultSet directly instead.', E_USER_DEPRECATED);

/**
 * @deprecated This class has been deprecated. Please use the ResultSet directly instead.
 */
class HybridResult
{
    protected $result;
    protected $transformed;

    public function __construct(Result $result, $transformed = null)
    {
        $this->result = $result;
        $this->transformed = $transformed;
    }

    public function getTransformed()
    {
        return $this->transformed;
    }

    public function getResult()
    {
        return $this->result;
    }
}
