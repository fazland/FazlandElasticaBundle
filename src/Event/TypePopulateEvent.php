<?php

declare(strict_types=1);

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Fazland <https://github.com/Fazland/FazlandElasticaBundle/graphs/contributors>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Event;

use Elastica\Type;

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
     * @var Type
     */
    private $type;

    public function __construct(Type $type)
    {
        parent::__construct($type->getIndex());

        $this->type = $type;
    }

    /**
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }
}
