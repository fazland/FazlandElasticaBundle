<?php

namespace Fazland\ElasticaBundle\Index;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Event\IndexResetEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes and recreates indexes.
 */
class Resetter
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @param IndexManager             $indexManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(IndexManager $indexManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;
        $this->indexManager = $indexManager;
    }

    /**
     * Deletes and recreates the named index. If populating, creates a new index
     * with a randomised name for an alias to be set after population.
     *
     * @param Index $index
     */
    public function resetIndex(Index $index)
    {
        $this->dispatcher->dispatch(IndexResetEvent::PRE_INDEX_RESET, new IndexResetEvent($index));
        $index->reset();
        $this->dispatcher->dispatch(IndexResetEvent::POST_INDEX_RESET, new IndexResetEvent($index));
    }

    /**
     * A command run when a population has finished.
     *
     * @param Index $index
     */
    public function finalize(Index $index)
    {
        $index->getAliasStrategy()->finalize();
    }
}
