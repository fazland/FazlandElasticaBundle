<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Event;

final class Events
{
    const REQUEST = 'elastica.request';
    const RESPONSE = 'elastica.response';

    const PRE_INDEX_POPULATE = 'elastica.index.index_pre_populate';
    const POST_INDEX_POPULATE = 'elastica.index.index_post_populate';

    const PRE_INDEX_RESET = 'elastica.index.pre_reset';
    const POST_INDEX_RESET = 'elastica.index.post_reset';

    const PRE_TRANSFORM = 'fazland_elastica.pre_transform';
    const POST_TRANSFORM = 'fazland_elastica.post_transform';

    const PRE_TYPE_POPULATE = 'elastica.index.type_pre_populate';
    const POST_TYPE_POPULATE = 'elastica.index.type_post_populate';
    const TYPE_POPULATE = 'elastica.index.type_populate';
}
