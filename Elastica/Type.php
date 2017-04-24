<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\TypePopulateEvent;
use Fazland\ElasticaBundle\Index\MappingBuilder;
use Fazland\ElasticaBundle\Provider\IndexableInterface;
use Fazland\ElasticaBundle\Provider\ProviderInterface;
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

    public function __construct(Elastica\Index $index, TypeConfig $typeConfig)
    {
        parent::__construct($index, $typeConfig->getName());

        $this->typeConfig = $typeConfig;
    }

    public function sendMapping()
    {
        $builder = $this->getMappingBuilder();
        $mapping = new Elastica\Type\Mapping();

        foreach ($builder->buildTypeMapping($this->typeConfig) as $name => $field) {
            $mapping->setParam($name, $field);
        }

        $this->setMapping($mapping);
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

        $options = $resolver->resolve($options);

        $this->eventDispatcher->dispatch(Events::PRE_TYPE_POPULATE, new TypePopulateEvent($this));

        $i = 0;
        $objects = [];
        foreach ($this->provider->provide($options['offset'], $options['size']) as $object) {
            $i++;
            $objects[] = $object;

            if (count($objects) >= $options['batch-size']) {
                $this->persistObjects($objects);
                $this->provider->clear();
            }
        }

        if (count($objects) > 0) {
            $this->persistObjects($objects);
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
        return $this->provider;
    }

    /**
     * @param ModelToElasticaTransformerInterface $modelTransformer
     */
    public function setModelTransformer(ModelToElasticaTransformerInterface $modelTransformer)
    {
        $this->modelTransformer = $modelTransformer;
    }

    public function persistObject($object)
    {
        $this->persistObjects([$object]);
    }

    public function persistObjects(array $objects)
    {
        $docs = array_map(function ($doc) {
            if ($doc instanceof Elastica\Document) {
                return $doc;
            }

            return $this->modelTransformer->transform($doc, $this->typeConfig->getMapping());
        }, $objects);

        $this->addDocuments($docs);
    }

    protected function getMappingBuilder()
    {
        return new MappingBuilder();
    }
}