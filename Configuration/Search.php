<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Configuration;

use Fazland\ElasticaBundle\Annotation\Search as BaseSearch;

/**
 * Annotation class for setting search repository.
 *
 * @Annotation
 *
 * @deprecated Use Fazland\ElasticaBundle\Annotation\Search instead
 * @Target("CLASS")
 */
class Search extends BaseSearch
{
}
