<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Elastica\Index;
use Fazland\ElasticaBundle\Configuration\TypeConfig;
use Fazland\ElasticaBundle\Index\MappingBuilder;

class Type extends Elastica\Type
{
    /**
     * @var TypeConfig
     */
    private $typeConfig;

    public function __construct(Index $index, TypeConfig $typeConfig)
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

    protected function getMappingBuilder()
    {
        return new MappingBuilder();
    }
}
