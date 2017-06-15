<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Elastica\ResultSet\BuilderInterface;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Elastica\ResultSet\Builder;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\TypePopulateEvent;
use Fazland\ElasticaBundle\Index\MappingBuilder;
use Fazland\ElasticaBundle\Provider\NullProvider;
use Fazland\ElasticaBundle\Provider\ProviderInterface;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Type extends Elastica\Type
{
    /**
     * @var TypeConfig
     */
    private $typeConfig;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ProviderInterface
     */
    private $provider;

    /**
     * @var ModelToElasticaTransformerInterface
     */
    private $modelTransformer;

    /**
     * @var ElasticaToModelTransformerInterface
     */
    private $elasticaTransformer;

    /**
     * @var Builder
     */
    private $defaultBuilder;

    public function __construct(Elastica\Index $index, TypeConfig $typeConfig)
    {
        parent::__construct($index, $typeConfig->getName());

        $this->typeConfig = $typeConfig;
    }

    public function buildMapping(): Elastica\Type\Mapping
    {
        $builder = $this->getMappingBuilder();
        $mapping = new Elastica\Type\Mapping();

        foreach ($builder->buildTypeMapping($this->typeConfig) as $name => $field) {
            $mapping->setParam($name, $field);
        }

        return $mapping;
    }

    public function sendMapping()
    {
        $this->setMapping($this->buildMapping());
    }

    public function populate(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'offset' => null,
            'size' => null,
            'sleep' => null,
            'batch_size' => 100,
            'ignore_errors' => false,
        ]);
        $resolver->setAllowedTypes('offset', ['null', 'int']);
        $resolver->setAllowedTypes('size', ['null', 'int']);
        $resolver->setAllowedTypes('batch_size', ['null', 'int']);
        $resolver->setAllowedTypes('sleep', ['null', 'int']);

        $options = $resolver->resolve($options);
        $provider = $this->getProvider();

        $this->eventDispatcher->dispatch(Events::PRE_TYPE_POPULATE, new TypePopulateEvent($this));

        $i = 0;
        $objects = [];
        foreach ($provider->provide($options['offset'], $options['size']) as $object) {
            ++$i;
            $objects[] = $object;

            $this->eventDispatcher->dispatch(Events::TYPE_POPULATE, new TypePopulateEvent($this));

            if (count($objects) >= $options['batch_size']) {
                $this->persist(...$objects);
                $objects = [];

                $provider->clear();

                if ($options['sleep']) {
                    sleep($options['sleep']);
                }
            }
        }

        if (count($objects) > 0) {
            $this->persist(...$objects);
            $this->provider->clear();
        }

        $this->eventDispatcher->dispatch(Events::POST_TYPE_POPULATE, new TypePopulateEvent($this));

        $this->_index->refresh();
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProviderInterface $provider
     */
    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        if (null === $this->provider) {
            return new NullProvider();
        }

        return $this->provider;
    }

    /**
     * @param ModelToElasticaTransformerInterface $modelTransformer
     */
    public function setModelTransformer(ModelToElasticaTransformerInterface $modelTransformer)
    {
        $this->modelTransformer = $modelTransformer;
    }

    /**
     * @param ElasticaToModelTransformerInterface $elasticaTransformer
     */
    public function setElasticaTransformer(ElasticaToModelTransformerInterface $elasticaTransformer)
    {
        $this->elasticaTransformer = $elasticaTransformer;
    }

    /**
     * Persist/update one or more objects to this type.
     *
     * @param array ...$objects
     */
    public function persist(...$objects)
    {
        $docs = array_map(function ($doc) {
            if ($doc instanceof Elastica\Document) {
                return $doc;
            }

            $doc = $this->modelTransformer->transform($doc, $this->typeConfig->getMapping());
            $doc->setDocAsUpsert(true);

            return $doc;
        }, $objects);

        $this->addDocuments($docs);
    }

    /**
     * Remove one or more objects from this type.
     *
     * @param array ...$objects
     */
    public function unpersist(...$objects)
    {
        $docs = array_map(function ($doc) {
            if ($doc instanceof Elastica\Document) {
                return $doc;
            }

            return $this->modelTransformer->transform($doc, []);
        }, $objects);

        $this->deleteDocuments($docs);
    }

    public function createSearch($query = '', $options = null, BuilderInterface $builder = null)
    {
        if (null === $builder) {
            $builder = $this->getDefaultResultSetBuilder();
        }

        $search = parent::createSearch($query, $options, $builder);
        if (! $search->getQuery()->hasParam('_source') &&
            null !== ($fields = $this->typeConfig->getFetchFields())) {
            $search->getQuery()->setSource($fields);
        }

        return $search;
    }

    protected function getMappingBuilder()
    {
        return new MappingBuilder();
    }

    protected function getDefaultResultSetBuilder()
    {
        if (null !== $this->defaultBuilder) {
            return $this->defaultBuilder;
        }

        $this->defaultBuilder = new Builder();

        if (null !== $this->elasticaTransformer) {
            $this->defaultBuilder->setTransformer($this->elasticaTransformer);
        }

        return $this->defaultBuilder;
    }
}
