<?php

namespace Fazland\ElasticaBundle\Provider;

final class NullProvider implements CountAwareProviderInterface
{
    public function count(int $offset = null, int $size = null)
    {
        return 0;
    }

    public function provide(int $offset = null, int $size = null)
    {
        return [];
    }

    public function clear()
    {
        // Do nothing
    }
}
