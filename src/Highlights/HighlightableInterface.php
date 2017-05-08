<?php

namespace Fazland\ElasticaBundle\Highlights;

/**
 * Indicates that the model should have elastica highlights injected.
 */
interface HighlightableInterface
{
    /**
     * Set ElasticSearch highlight data.
     *
     * @param array $highlights array of highlight strings
     */
    public function setElasticHighlights(array $highlights);
}
