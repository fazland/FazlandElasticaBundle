<?php

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Elasticsearch\Endpoints\AbstractEndpoint;
use Fazland\ElasticaBundle\Configuration\IndexConfig;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\IndexPopulateEvent;
use Fazland\ElasticaBundle\Event\IndexResetEvent;
use Fazland\ElasticaBundle\Exception\UnknownTypeException;
use Fazland\ElasticaBundle\Index\AliasStrategy\AliasStrategyInterface;
use Fazland\ElasticaBundle\Index\AliasStrategy\NullAliasStrategy;
use Fazland\ElasticaBundle\Index\MappingBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Overridden Elastica Index class that provides dynamic index name changes.
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

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(Elastica\Client $client, IndexConfig $indexConfig)
    {
        $this->indexConfig = $indexConfig;

        parent::__construct($client, $this->indexConfig->getElasticSearchName());
    }

    /**
     * Resets the current index and push its mapping.
     *
     * @param bool $populate Flag to indicate whether the reset is part of the populate process.
     *
     * @return void
     */
    public function reset($populate = false)
    {
        $this->eventDispatcher->dispatch(Events::PRE_INDEX_RESET, new IndexResetEvent($this));

        $this->overrideName();

        $mappingBuilder = $this->getMappingBuilder();
        $mapping = $mappingBuilder->buildIndexMapping($this->indexConfig);

        $this->create($mapping, true);

        $this->getAliasStrategy()->prePopulate();

        $this->eventDispatcher->dispatch(Events::POST_INDEX_RESET, new IndexResetEvent($this));

        if (! $populate) {
            $this->getAliasStrategy()->finalize();
        }
    }

    public function populate(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-reset' => false,
            'offset' => null,
            'size' => null,
            'sleep' => null,
            'batch_size' => 100,
            'ignore_errors' => false,
        ]);

        $options = $resolver->resolve($options);
        $reset = ! $options['no-reset'];

        unset($options['no-reset']);

        if ($reset) {
            $this->reset(true);
        }

        $this->eventDispatcher->dispatch(Events::PRE_INDEX_POPULATE, new IndexPopulateEvent($this));

        foreach ($this->indexConfig->getTypes() as $typeConfig) {
            $this->getType($typeConfig->getName())->populate($options);
        }

        $this->eventDispatcher->dispatch(Events::POST_INDEX_POPULATE, new IndexPopulateEvent($this));

        if ($reset) {
            $this->getAliasStrategy()->finalize();
        }
    }

    public function setAliasStrategy(AliasStrategyInterface $aliasStrategy = null)
    {
        $this->aliasStrategy = $aliasStrategy;
    }

    public function getAliasStrategy(): AliasStrategyInterface
    {
        if (null === $this->aliasStrategy) {
            $this->aliasStrategy = new NullAliasStrategy($this);
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

    public function getAlias(): string
    {
        return $this->indexConfig->getElasticSearchName();
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function request($path, $method, $data = [], array $query = [])
    {
        $name = $this->getAliasStrategy()->getName($method, $path);
        $path = $name.'/'.$path;

        return $this->getClient()->request($path, $method, $data, $query);
    }

    /**
     * @inheritdoc
     */
    public function requestEndpoint(AbstractEndpoint $endpoint)
    {
        $cloned = clone $endpoint;
        $cloned->setIndex($this->getName());

        $name = $this->getAliasStrategy()->getName($cloned->getMethod(), $cloned->getURI());
        $cloned->setIndex($name);

        return $this->getClient()->requestEndpoint($cloned);
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
        $type = new Type($this, $typeConfig);
        $type->setEventDispatcher($this->eventDispatcher);

        return $type;
    }
}
