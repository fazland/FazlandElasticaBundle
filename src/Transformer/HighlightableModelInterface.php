<?php

namespace Fazland\ElasticaBundle\Transformer;

use Fazland\ElasticaBundle\Highlights\HighlightableInterface as BaseInterface;

@trigger_error(__NAMESPACE__.'\\HighlightableModelInterface is deprecated. Please use '.BaseInterface::class.' instead', E_USER_DEPRECATED);

/**
 * @deprecated This interface is deprecated. Please use Fazland\ElasticaBundle\Highlights\HighlightableModelInterface instead.
 */
interface HighlightableModelInterface extends BaseInterface
{
}
