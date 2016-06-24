<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Configuration\Source;

/**
 * Represents a source of index and type information (ie, the Container configuration or
 * annotations).
 */
interface SourceInterface
{
    /**
     * Should return all configuration available from the data source.
     *
     * @return \Fazland\ElasticaBundle\Configuration\IndexConfig[]
     */
    public function getConfiguration();
}
