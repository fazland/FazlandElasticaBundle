<?php

namespace Fazland\ElasticaBundle\Tests\Functional\app\complete;

use Fazland\ElasticaBundle\Provider\ProviderInterface;

class Provider implements ProviderInterface
{
    /**
     * @var string
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function provide(int $offset = null, int $size = null)
    {
        return [];
    }

    public function clear()
    {
    }
}
