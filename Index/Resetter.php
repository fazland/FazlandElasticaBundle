<?php

namespace Fazland\ElasticaBundle\Index;

use Elastica\Exception\ResponseException;
use Elastica\Type\Mapping;
use Fazland\ElasticaBundle\Configuration\ConfigManager;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Event\TypeResetEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes and recreates indexes.
 */
class Resetter
{
    /**
     * @var AliasProcessor
     */
    private $aliasProcessor;

    /***
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var MappingBuilder
     */
    private $mappingBuilder;

    /**
     * @param ConfigManager            $configManager
     * @param IndexManager             $indexManager
     * @param AliasProcessor           $aliasProcessor
     * @param MappingBuilder           $mappingBuilder
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ConfigManager $configManager,
        IndexManager $indexManager,
        AliasProcessor $aliasProcessor,
        MappingBuilder $mappingBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->aliasProcessor = $aliasProcessor;
        $this->configManager = $configManager;
        $this->dispatcher = $eventDispatcher;
        $this->indexManager = $indexManager;
        $this->mappingBuilder = $mappingBuilder;
    }

    /**
     * Deletes and recreates the named index. If populating, creates a new index
     * with a randomised name for an alias to be set after population.
     *
     * @throws \InvalidArgumentException if no index exists for the given name
     */
    public function resetIndex(Index $index)
    {
        $index->reset();
    }

    /**
     * Deletes and recreates a mapping type for the named index.
     *
     * @param string $indexName
     * @param string $typeName
     *
     * @throws \InvalidArgumentException if no index or type mapping exists for the given names
     * @throws ResponseException
     */
    public function resetIndexType($indexName, $typeName)
    {
        $typeConfig = $this->configManager->getTypeConfiguration($indexName, $typeName);

        $this->resetIndex($indexName, true);

        $index = $this->indexManager->getIndex($indexName);
        $type = $index->getType($typeName);

        $event = new TypeResetEvent($indexName, $typeName);
        $this->dispatcher->dispatch(TypeResetEvent::PRE_TYPE_RESET, $event);

        $mapping = new Mapping();
        foreach ($this->mappingBuilder->buildTypeMapping($typeConfig) as $name => $field) {
            $mapping->setParam($name, $field);
        }

        $type->setMapping($mapping);
        $this->dispatcher->dispatch(TypeResetEvent::POST_TYPE_RESET, $event);
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
