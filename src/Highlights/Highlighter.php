<?php

namespace Fazland\ElasticaBundle\Highlights;

use Doctrine\Common\Persistence\ManagerRegistry;
use Fazland\ElasticaBundle\Highlights\HighlightableInterface;
use Fazland\ElasticaBundle\Highlights\HighlighterInterface;
use Fazland\ElasticaBundle\Transformer\ObjectFetcherInterface;

final class Highlighter implements HighlighterInterface
{
    public function setHighlights(array $objects, array $highlights)
    {
        foreach ($objects as $key => $object) {
            if ($object instanceof HighlightableInterface && isset($highlights[$key])) {
                $object->setElasticHighlights($highlights[$key]);
            }
        }
    }
}
