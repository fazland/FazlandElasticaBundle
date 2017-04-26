<?php

namespace Fazland\ElasticaBundle\Provider;

/**
 * Insert application domain objects into elastica types.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ProviderInterface
{
    /**
     * Provides objects for index/type population.
     *
     * @param int $offset
     * @param int $size
     *
     * @return iterable
     */
    public function provide(int $offset = null, int $size = null);

    /**
     * Clean up resources/free used memory between two batch operations.
     */
    public function clear();
}
