<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Event;

use Elastica\Index;
use Symfony\Component\EventDispatcher\Event;

class IndexEvent extends Event
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * @return Index
     */
    public function getIndex(): Index
    {
        return $this->index;
    }
}
