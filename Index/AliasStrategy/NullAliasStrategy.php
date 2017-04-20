<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

final class NullAliasStrategy implements AliasStrategyInterface
{
    public function buildName(string $originalName): string
    {
        return $originalName;
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
