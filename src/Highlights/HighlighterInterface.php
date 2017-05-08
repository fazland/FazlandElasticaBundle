<?php

namespace Fazland\ElasticaBundle\Highlights;

interface HighlighterInterface
{
    /**
     * Sets the highlights into the objects.
     *
     * @param array $objects
     * @param array $highlights
     */
    public function setHighlights(array $objects, array $highlights);
}
