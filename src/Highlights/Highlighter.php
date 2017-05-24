<?php

namespace Fazland\ElasticaBundle\Highlights;

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
