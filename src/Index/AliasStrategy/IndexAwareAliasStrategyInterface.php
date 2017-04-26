<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

use Fazland\ElasticaBundle\Elastica\Index;

interface IndexAwareAliasStrategyInterface extends AliasStrategyInterface
{
    public function setIndex(Index $index);
}
