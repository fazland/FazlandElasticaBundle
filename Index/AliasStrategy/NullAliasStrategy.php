<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

use Fazland\ElasticaBundle\Elastica\Index;

final class NullAliasStrategy implements AliasStrategyInterface
{
    /**
     * @var Index
     */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function buildName(string $originalName): string
    {
        return $originalName;
    }

    public function getName(string $method, string $path): string
    {
        return $this->index->getName();
    }

    public function prePopulate()
    {
        // Do nothing
    }

    public function finalize()
    {
        // Do nothing
    }
}
