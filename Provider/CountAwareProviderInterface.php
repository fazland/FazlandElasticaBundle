<?php

namespace Fazland\ElasticaBundle\Provider;

/**
 * Insert application domain objects into elastica types.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface CountAwareProviderInterface extends ProviderInterface
{
    /**
     * Provides objects for index/type population.
     *
     * @param int $offset
     * @param int $size
     *
     * @return iterable
     */
    public function count(int $offset = null, int $size = null);
}
