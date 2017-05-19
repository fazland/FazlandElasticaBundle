<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Index\AliasStrategy;

use Elastica\Response;
use Elasticsearch\Endpoints\Indices\Alias\Get;
use Elasticsearch\Endpoints\Indices\Aliases\Update;
use Elasticsearch\Endpoints\Indices\Delete;
use Fazland\ElasticaBundle\Elastica\Client;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\AliasStrategy\SimpleAliasStrategy;
use phpmock\prophecy\PHPProphet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class SimpleAliasStrategyTest extends TestCase
{
    /**
     * @var SimpleAliasStrategy
     */
    private $simpleAliasStrategy;

    protected function setUp()
    {
        $this->simpleAliasStrategy = new SimpleAliasStrategy();
    }

    public function testBuildNameReturnAnExpectedName()
    {
        $prophet = new PHPProphet();
        $ns = $prophet->prophesize('Fazland\ElasticaBundle\Index\AliasStrategy');
        $ns->date(Argument::any())->willReturn('2017_04_12_112123');
        $ns->reveal();

        $newName = $this->simpleAliasStrategy->buildName('aliasname');
        $this->assertEquals('aliasname_2017_04_12_112123', $newName);

        $prophet->checkPredictions();
    }

    public function testSetIndexShouldSetClient()
    {
        $index = $this->prophesize(Index::class);
        $index->getClient()->shouldBeCalled();
        $this->simpleAliasStrategy->setIndex($index->reveal());
    }

    public function testFinalizeShouldDeletePreviousIndexAndReassignAlias()
    {
        /** @var Index|ObjectProphecy $index */
        $index = $this->prophesize(Index::class);
        /** @var Client $client */
        $client = $this->prophesize(Client::class);

        $response = $this->prophesize(Response::class);
        $response->getData()->willReturn(['index1']);

        $client->requestEndpoint(Argument::type(Get::class))->willReturn($response->reveal());
        $client->requestEndpoint(Argument::type(Update::class))->shouldBeCalledTimes(1);
        $client->requestEndpoint(Argument::type(Delete::class))->shouldBeCalledTimes(1);

        $index->getClient()->willReturn($client->reveal());
        $this->simpleAliasStrategy->setIndex($index->reveal());
        $index->getAlias()->willReturn('alias');
        $index->getName()->shouldBeCalled();

        $this->simpleAliasStrategy->finalize();
    }

    public function testGetNameShouldWrapGetNameOfIndexClass()
    {
        /** @var Index|ObjectProphecy $index */
        $index = $this->prophesize(Index::class);
        $index->getName()->willReturn('index_1');
        $index->getClient()->shouldBeCalled();
        $this->simpleAliasStrategy->setIndex($index->reveal());

        $this->simpleAliasStrategy->getName('method', 'path');
    }

    public function testFinalizeIfRetrieveMoreThanOneIndexWithSameAliasWillNotDeleteThem()
    {
        /** @var Index|ObjectProphecy $index */
        $index = $this->prophesize(Index::class);
        /** @var Client $client */
        $client = $this->prophesize(Client::class);

        $response = $this->prophesize(Response::class);
        $response->getData()->willReturn(['index1', 'index2']);

        $client->requestEndpoint(Argument::type(Get::class))->willReturn($response->reveal());
        $client->requestEndpoint(Argument::type(Update::class))->shouldBeCalledTimes(1);
        $client->requestEndpoint(Argument::type(Delete::class))->shouldNotBeCalled();

        $index->getClient()->willReturn($client->reveal());
        $this->simpleAliasStrategy->setIndex($index->reveal());
        $index->getAlias()->willReturn('alias');
        $index->getName()->shouldBeCalled();

        $this->simpleAliasStrategy->finalize();
    }
}
