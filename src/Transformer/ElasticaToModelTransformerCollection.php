<?php

namespace Fazland\ElasticaBundle\Transformer;

use Elastica\ResultSet;
use Fazland\ElasticaBundle\HybridResult;

/**
 * Holds a collection of transformers for an index wide transformation.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class ElasticaToModelTransformerCollection implements ElasticaToModelTransformerInterface
{
    /**
     * @var ElasticaToModelTransformerInterface[]
     */
    protected $transformers = [];

    /**
     * @param array $transformers
     */
    public function __construct(array $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * @deprecated Will be removed in 6.0
     */
    public function getObjectClass()
    {
        @trigger_error(__METHOD__.' is deprecated and will be removed in a future version.', E_USER_DEPRECATED);

        return array_map(function (ElasticaToModelTransformerInterface $transformer) {
            if (method_exists($transformer, 'getObjectClass')) {
                return $transformer->getObjectClass();
            }
        }, $this->transformers);
    }

    /**
     * @deprecated Will be removed in 6.0
     */
    public function getIdentifierField()
    {
        @trigger_error(__METHOD__.' is deprecated and will be removed in a future version.', E_USER_DEPRECATED);

        return array_map(function (ElasticaToModelTransformerInterface $transformer) {
            if (method_exists($transformer, 'getIdentifierField')) {
                return $transformer->getIdentifierField();
            }
        }, $this->transformers);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($elasticaObjects)
    {
        $sorted = [];
        foreach ($elasticaObjects as $object) {
            $sorted[$object->getType()][] = $object;
        }

        $func = function () use ($sorted) {
            foreach ($sorted as $type => $objects) {
                yield from $this->transformers[$type]->transform($objects);
            }
        };

        return iterator_to_array($func(), false);
    }

    /**
     * Gets hybrid results.
     *
     * @param array|ResultSet $elasticaObjects
     *
     * @return HybridResult[]
     *
     * @deprecated Hybrid results have been deprecated. Use ResultSet instead.
     */
    public function hybridTransform($elasticaObjects)
    {
        @trigger_error('Hybrid results have been deprecated. Please use the bundle\'s ResultSet directly instead', E_USER_DEPRECATED);

        $sorted = [];
        foreach ($elasticaObjects as $object) {
            $sorted[$object->getType()][] = $object;
        }

        $transformed = [];
        foreach ($sorted as $type => $objects) {
            $transformed[$type] = $this->transformers[$type]->transform($objects);
        }

        $func = function () use ($sorted, $transformed) {
            foreach ($sorted as $type => $objects) {
                foreach ($objects as $id => $object) {
                    yield new HybridResult($object, $transformed[$object->getType()][$object->getId()] ?? null);
                }
            }
        };

        return iterator_to_array($func(), false);
    }
}
