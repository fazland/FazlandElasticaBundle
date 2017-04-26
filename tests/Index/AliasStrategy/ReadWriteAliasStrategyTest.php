<?php

namespace Fazland\ElasticaBundle\Tests\Index\AliasStrategy;

use Elastica\Request;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\AliasStrategy\ReadWriteAliasStrategy;
use phpmock\prophecy\PHPProphet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ReadWriteAliasStrategyTest extends TestCase
{
    /**
     * @var ReadWriteAliasStrategy
     */
    private $readWriteAliasStrategy;

    protected function setUp()
    {
        $this->readWriteAliasStrategy = new ReadWriteAliasStrategy();
    }

    public function testBuildNameReturnAnExpectedName()
    {
        $prophet = new PHPProphet();
        $ns = $prophet->prophesize('Fazland\ElasticaBundle\Index\AliasStrategy');
        $ns->date(Argument::any())->willReturn('2017_04_12_112123');
        $ns->reveal();

        $newName = $this->readWriteAliasStrategy->buildName('aliasname');
        $this->assertEquals('aliasname_2017_04_12_112123', $newName);

        $prophet->checkPredictions();
    }

    public function testSetIndexShouldSetClient()
    {
        $index = $this->prophesize(Index::class);
        $index->getClient()->shouldBeCalled();
        $this->readWriteAliasStrategy->setIndex($index->reveal());
    }

    public function testGetNameReturnAnExpectedAliasNameIfIsAGetRequest()
    {
        /** @var Index|ObjectProphecy $index */
        $index = $this->prophesize(Index::class);
        $index->getClient()->shouldBeCalled();
        $index->getName()->willReturn('index1');
        $this->readWriteAliasStrategy->setIndex($index->reveal());
        $name = $this->readWriteAliasStrategy->getName(Request::GET, '/index1/type1/_search');

        $this->assertEquals('index1_read', $name);
    }

    public function testGetNameReturnAnExpectedAliasNameIfIsAPostRequest()
    {
        /** @var Index|ObjectProphecy $index */
        $index = $this->prophesize(Index::class);
        $index->getClient()->shouldBeCalled();
        $index->getName()->willReturn('index1');
        $this->readWriteAliasStrategy->setIndex($index->reveal());
        $name = $this->readWriteAliasStrategy->getName(Request::POST, '/index1/type1/');

        $this->assertEquals('index1_write', $name);
    }
}
