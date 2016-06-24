<?php

namespace Fazland\ElasticaBundle\Tests\Resetter;

use Elastica\Result;
use Fazland\ElasticaBundle\HybridResult;

class HybridResultTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformedResultDefaultsToNull()
    {
        $result = new Result(array());

        $hybridResult = new HybridResult($result);

        $this->assertSame($result, $hybridResult->getResult());
        $this->assertNull($hybridResult->getTransformed());
    }
}
