<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Fazland <https://github.com/Fazland/FazlandElasticaBundle/graphs/contributors>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Event;

/**
 * Type Populate Event.
 *
 * @author Oleg Andreyev <oleg.andreyev@intexsys.lv>
 */
class TypePopulateEvent extends IndexPopulateEvent
{
    /**
     * @deprecated
     */
    const PRE_TYPE_POPULATE = Events::PRE_TYPE_POPULATE;

    /**
     * @deprecated
     */
    const POST_TYPE_POPULATE = Events::POST_TYPE_POPULATE;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $index
     * @param string $type
     * @param bool   $reset
     * @param array  $options
     */
    public function __construct($index, $type, $reset, $options)
    {
        parent::__construct($index, $reset, $options);

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
