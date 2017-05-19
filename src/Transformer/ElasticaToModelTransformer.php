<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Fazland <https://github.com/Fazland/FazlandElasticaBundle/graphs/contributors>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Transformer;

use Fazland\ElasticaBundle\Exception\ObjectMissingException;
use Fazland\ElasticaBundle\Highlights\HighlighterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElasticaToModelTransformer implements ElasticaToModelTransformerInterface
{
    /**
     * ObjectFinder instance.
     *
     * @var ObjectFetcherInterface
     */
    protected $objectFetcher;

    /**
     * Highlighter instance.
     *
     * @var HighlighterInterface
     */
    protected $highlighter;

    /**
     * Options array.
     *
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    /**
     * Sets the ObjectFetcher instance.
     *
     * @param ObjectFetcherInterface $objectFetcher
     * @required
     */
    public function setObjectFetcher(ObjectFetcherInterface $objectFetcher)
    {
        $this->objectFetcher = $objectFetcher;
    }

    /**
     * Sets the Highlighter instance.
     *
     * @param HighlighterInterface $highlighter
     */
    public function setHighlighter(HighlighterInterface $highlighter = null)
    {
        $this->highlighter = $highlighter;
    }

    /**
     * @inheritDoc
     **/
    public function transform($results)
    {
        $ids = $highlights = [];
        foreach ($results as $result) {
            $ids[] = $result->getId();
            $highlights[$result->getId()] = $result->getHighlights();
        }

        $objects = $this->objectFetcher->find(...$ids);
        if ($objects instanceof \Iterator) {
            $objects = iterator_to_array($objects);
        }

        $objectsCnt = count(array_filter($objects));
        $elasticaObjectsCnt = count($results);
        if (! $this->options['ignore_missing'] && $objectsCnt < $elasticaObjectsCnt) {
            throw ObjectMissingException::create($objectsCnt, $ids);
        }

        if (null !== $this->highlighter) {
            $this->highlighter->setHighlights($objects, $highlights);
        }

        // BC: Keep compatibility with custom sorting closure.
        // sort objects in the order of ids
        $idPos = array_flip($ids);
        if (null !== $closure = $this->getSortingClosure($idPos, $this->options['identifier'])) {
            @trigger_error('Extending getSortingClosure is deprecated. Please use a custom object finder for your custom object sorting', E_USER_DEPRECATED);
            uasort($objects, $closure);
        }

        return $objects;
    }

    /**
     * Returns a sorting closure to be used with usort() to put retrieved objects
     * back in the order that they were returned by ElasticSearch.
     *
     * @param array  $idPos
     * @param string $identifierPath
     *
     * @return callable
     *
     * @deprecated Sorting should be performed by the object finder
     */
    protected function getSortingClosure(array $idPos, $identifierPath)
    {
    }

    /**
     * Configures options accepted by this transformer.
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ignore_missing' => false,
            'identifier' => null,    // BC
        ]);

        $resolver->setAllowedTypes('ignore_missing', 'bool');
    }
}
