<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Index;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Event\IndexResetEvent;
use Fazland\ElasticaBundle\Index\AliasStrategy\AliasStrategyInterface;
use Fazland\ElasticaBundle\Index\IndexManager;
use Fazland\ElasticaBundle\Index\Resetter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ResetterTest extends TestCase
{
    /**
     * @var Resetter
     */
    private $resetter;

    /**
     * @var IndexManager|ObjectProphecy
     */
    private $indexManager;

    /**
     * @var EventDispatcherInterface|ObjectProphecy
     */
    private $eventDispatcher;

    protected function setUp()
    {
        $this->indexManager = $this->prophesize(IndexManager::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->resetter = new Resetter($this->indexManager->reveal(), $this->eventDispatcher->reveal());
    }

    public function testResetIndexShouldDispatchEvents()
    {
        $index = $this->prophesize(Index::class);
        $index->reset()->shouldBeCalled();

        $this->eventDispatcher->dispatch(IndexResetEvent::PRE_INDEX_RESET, Argument::type(IndexResetEvent::class))
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(IndexResetEvent::POST_INDEX_RESET, Argument::type(IndexResetEvent::class))
            ->shouldBeCalled();

        $this->resetter->resetIndex($index->reveal());
    }

    public function testFinalizeShouldCallAliasStrategyFinalizer()
    {
        $index = $this->prophesize(Index::class);
        $index->getAliasStrategy()->willReturn(
            $aliasStrategy = $this->prophesize(AliasStrategyInterface::class)
        );

        $aliasStrategy->finalize()->shouldBeCalled();

        $this->resetter->finalize($index->reveal());
    }
}
