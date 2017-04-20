<?php

namespace Fazland\ElasticaBundle\Elastica;

use Elastica\Client;
use Elastica\Index as BaseIndex;
use Fazland\ElasticaBundle\Configuration\IndexConfig;
use Fazland\ElasticaBundle\Index\AliasStrategy\AliasStrategyInterface;
use Fazland\ElasticaBundle\Index\AliasStrategy\NullAliasStrategy;
use Fazland\ElasticaBundle\Index\MappingBuilder;

/**
 * Overridden Elastica Index class that provides dynamic index name changes.
 *
 * @author Konstantin Tjuterev <kostik.lv@gmail.com>
 */
class Index extends BaseIndex
{
    /**
     * Store the original name
     *
     * @var string
     */
    private $originalName;

    /**
     * Stores created types to avoid recreation.
     *
     * @var array
     */
    private $typeCache = [];

    /**
     * @var AliasStrategyInterface
     */
    private $aliasStrategy;

    /**
     * @var IndexConfig
     */
    private $indexConfig;

    public function __construct(Client $client, IndexConfig $indexConfig)
    {
        $this->indexConfig = $indexConfig;

        parent::__construct($client, $this->indexConfig->getElasticSearchName());
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->overrideName();

        $mappingBuilder = $this->getMappingBuilder();
        $mapping = $mappingBuilder->buildIndexMapping($this->indexConfig);

        $this->create($mapping, true);

        $this->aliasStrategy->prePopulate();
    }

    public function setAliasStrategy(AliasStrategyInterface $aliasStrategy = null)
    {
        $this->aliasStrategy = $aliasStrategy;
    }

    public function getAliasStrategy(): AliasStrategyInterface
    {
        if (null === $this->aliasStrategy) {
            $this->aliasStrategy = new NullAliasStrategy();
        }

        return $this->aliasStrategy;
    }

    /**
     * Reassign index name for aliasing.
     *
     * While it's technically a regular setter for name property, it's specifically named overrideName, but not setName
     * since it's used for a very specific case and normally should not be used
     *
     * @return void
     */
    public function overrideName()
    {
        if (null === $this->originalName) {
            $this->originalName = $this->_name;
        }

        $this->_name = $this->aliasStrategy->buildName($this->originalName);
        $this->_name = sprintf('%s_%s', $this->originalName, date('Y-m-d-His'));
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    public function getType($type)
    {
        if (isset($this->typeCache[$type])) {
            return $this->typeCache[$type];
        }

        return $this->typeCache[$type] = parent::getType($type);
    }

    /**
     * Create a new instance of MappingBuilder
     *
     * @return MappingBuilder
     */
    protected function getMappingBuilder(): MappingBuilder
    {
        return new MappingBuilder();
    }
}
