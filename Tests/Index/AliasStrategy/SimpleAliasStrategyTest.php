<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Index\AliasStrategy;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\AliasStrategy\SimpleAliasStrategy;
use phpmock\prophecy\PHPProphet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SimpleAliasStrategyTest extends TestCase
{
    /**
     * @var SimpleAliasStrategy
     */
    private $simpleAliasStrategy;

    protected function setUp()
    {
        $index = $this->prophesize(Index::class);
        $this->simpleAliasStrategy = new SimpleAliasStrategy($index->reveal());
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
}
