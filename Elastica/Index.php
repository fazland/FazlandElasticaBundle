<?php

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Fazland\ElasticaBundle\Configuration\IndexConfig;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Exception\UnknownTypeException;
use Fazland\ElasticaBundle\Index\AliasStrategy\AliasStrategyInterface;
use Fazland\ElasticaBundle\Index\AliasStrategy\NullAliasStrategy;
use Fazland\ElasticaBundle\Index\MappingBuilder;

/**
 * Overridden Elastica Index class that provides dynamic index name changes.
 *
 * @author Konstantin Tjuterev <kostik.lv@gmail.com>
 */
class Index extends Elastica\Index
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
     * @var Elastica\Type[]
     */
    private $types = [];

    /**
     * @var AliasStrategyInterface
     */
    private $aliasStrategy;

    /**
     * @var IndexConfig
     */
    private $indexConfig;

    public function __construct(Elastica\Client $client, IndexConfig $indexConfig)
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

        $this->getAliasStrategy()->prePopulate();
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

        $this->_name = $this->getAliasStrategy()->buildName($this->originalName);
    }

    /**
     * @param string $name
     *
     * @return Elastica\Type
     */
    public function getType($name): Elastica\Type
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        if (! $this->indexConfig->hasType($name)) {
            throw new UnknownTypeException(sprintf('Unknown type "%s" for index "%s" requested.', $name, $this->indexConfig->getName()));
        }

        return $this->types[$name] = $this->createType($this->indexConfig->getType($name));
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

    /**
     * Creates a Type object.
     *
     * @param TypeConfig $typeConfig
     *
     * @return Elastica\Type
     */
    protected function createType(TypeConfig $typeConfig): Elastica\Type
    {
        return new Type($this, $typeConfig);
    }
}
