<?php

namespace Fazland\ElasticaBundle\Tests\Functional\app\complete;

use Elastica\Type;
use Fazland\ElasticaBundle\Finder\FinderInterface;

class Finder implements FinderInterface
{
    /**
     * @var Type
     */
    public $type;

    public function __construct(Type $type)
    {
        $this->type = $type;
    }

    public function find($query, $limit = null, $options = [])
    {
        // Dummy
    }
}
