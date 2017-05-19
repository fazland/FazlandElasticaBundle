<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\DataCollector;

use Fazland\ElasticaBundle\DataCollector\ElasticaDataCollector;
use Fazland\ElasticaBundle\Logger\ElasticaLogger;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Richard Miller <info@limethinking.co.uk>
 */
class ElasticaDataCollectorTest extends TestCase
{
    /**
     * @var ElasticaLogger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ElasticaDataCollector
     */
    private $collector;

    protected function setUp()
    {
        $this->logger = $this->prophesize(ElasticaLogger::class);
        $this->collector = new ElasticaDataCollector($this->logger->reveal());
    }

    public function testCorrectAmountOfQueries()
    {
        $this->logger->getNbQueries()->willReturn($totalQueries = rand());
        $this->logger->getQueries()->willReturn([]);

        $this->collector->collect($this->prophesize(Request::class)->reveal(), $this->prophesize(Response::class)->reveal());
        $this->assertEquals($totalQueries, $this->collector->getQueryCount());
    }

    public function testCorrectQueriesReturned()
    {
        $this->logger->getNbQueries()->willReturn(1);
        $this->logger->getQueries()->willReturn($queries = ['testQueries']);

        $this->collector->collect($this->prophesize(Request::class)->reveal(), $this->prophesize(Response::class)->reveal());
        $this->assertEquals($queries, $this->collector->getQueries());
    }

    public function testCorrectQueriesTime()
    {
        $queries = [[
            'engineMS' => 15,
            'executionMS' => 10,
        ], [
            'engineMS' => 25,
            'executionMS' => 20,
        ]];

        $this->logger->getNbQueries()->willReturn(2);
        $this->logger->getQueries()->willReturn($queries);

        $this->collector->collect($this->prophesize(Request::class)->reveal(), $this->prophesize(Response::class)->reveal());
        $this->assertEquals(40, $this->collector->getTime());
        $this->assertEquals(30, $this->collector->getExecutionTime());
    }

    public function testName()
    {
        $this->assertEquals('elastica', $this->collector->getName());
    }
}
