<?php

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

use Fazland\ElasticaBundle\Elastica\Index;

interface FactoryInterface
{
    public function factory(string $strategy, Index $index): AliasStrategyInterface;
}
