<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Event;

/**
 * Index ResetEvent.
 *
 * @author Oleg Andreyev <oleg.andreyev@intexsys.lv>
 */
class IndexResetEvent extends IndexEvent
{
    const PRE_INDEX_RESET = 'elastica.index.pre_reset';
    const POST_INDEX_RESET = 'elastica.index.post_reset';
}
