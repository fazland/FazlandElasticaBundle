<?php

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

use Fazland\ElasticaBundle\Elastica\Index;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Factory implements ContainerAwareInterface, FactoryInterface
{
    use ContainerAwareTrait;

    public function factory(string $strategy, Index $index): AliasStrategyInterface
    {
        switch ($strategy) {
            case 'simple':
                return new SimpleAliasStrategy($index);

            default:
                return $this->container->get($strategy);
        }
    }
}
