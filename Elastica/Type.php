<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Index\MappingBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function getMappingBuilder()
    {
        return new MappingBuilder();
    }
}
