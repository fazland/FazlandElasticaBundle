<?php

namespace Fazland\ElasticaBundle\Tests\Functional\app\complete;

use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;

class ETMTransformer implements ElasticaToModelTransformerInterface
{
    public function transform(array $elasticaObjects)
    {
        return [];
    }

    public function getObjectClass()
    {
        return \stdClass::class;
    }

    public function getIdentifierField()
    {
        return 'id';
    }
}
