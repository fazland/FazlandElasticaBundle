<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

interface AliasStrategyInterface
{
    public function buildName(string $originalName): string;

    public function getName(string $method, string $path): string;

    public function prePopulate();

    public function finalize();
}
