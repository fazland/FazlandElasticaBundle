<?php

namespace Fazland\ElasticaBundle\Propel;

use Fazland\ElasticaBundle\HybridResult;
use Fazland\ElasticaBundle\Transformer\AbstractElasticaToModelTransformer;
use Fazland\ElasticaBundle\Transformer\HighlightableModelInterface;

/**
 * Maps Elastica documents with Propel objects.
 *
 * This mapper assumes an exact match between Elastica document IDs and Propel
 * entity IDs.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class ElasticaToModelTransformer extends AbstractElasticaToModelTransformer
{
    /**
     * Propel model class to map to Elastica documents.
     *
     * @var string
     */
    protected $objectClass = null;

    /**
     * Transformer options.
     *
     * @var array
     */
    protected $options = [
        'hydrate' => true,
        'identifier' => 'id',
    ];

    /**
     * Constructor.
     *
     * @param string $objectClass
     * @param array  $options
     */
    public function __construct($objectClass, array $options = [])
    {
        $this->objectClass = $objectClass;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Transforms an array of Elastica document into an array of Propel entities
     * fetched from the database.
     *
     * @param array $elasticaObjects
     *
     * @return array|\ArrayObject
     */
    public function transform(array $elasticaObjects)
    {
        $ids = $highlights = [];
        foreach ($elasticaObjects as $elasticaObject) {
            $ids[] = $elasticaObject->getId();
            $highlights[$elasticaObject->getId()] = $elasticaObject->getHighlights();
        }

        $objects = $this->findByIdentifiers($ids, $this->options['hydrate']);

        if (! $this->options['ignore_missing'] && count($objects) < count($elasticaObjects)) {
            throw new \RuntimeException('Cannot find corresponding Propel objects for all Elastica results.');
        }

        $_objects = [];
        foreach ($objects as $object) {
            if ($objects instanceof HighlightableModelInterface) {
                $object->setElasticHighlights($highlights[$object->getId()]);
            }

            $_objects[] = $object;
        }

        return $_objects;
    }

    /**
     * Gets hybrid results.
     *
     * @param array $elasticaObjects
     *
     * @return HybridResult[]
     *
     * @deprecated Hybrid results have been deprecated. Use ResultSet instead.
     */
    public function hybridTransform(array $elasticaObjects)
    {
        @trigger_error('Hybrid results have been deprecated. Please use the bundle\'s ResultSet directly instead', E_USER_DEPRECATED);

        $objects = $this->transform($elasticaObjects);

        $result = [];
        for ($i = 0, $j = count($elasticaObjects); $i < $j; ++$i) {
            $result[] = new HybridResult($elasticaObjects[$i], $objects[$i]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierField()
    {
        return $this->options['identifier'];
    }

    /**
     * Fetch Propel entities for the given identifier values.
     *
     * If $hydrate is false, the returned array elements will be arrays.
     * Otherwise, the results will be hydrated to instances of the model class.
     *
     * @param array $identifierValues Identifier values
     * @param bool  $hydrate          Whether or not to hydrate the results
     *
     * @return array
     */
    protected function findByIdentifiers(array $identifierValues, $hydrate)
    {
        if (empty($identifierValues)) {
            return [];
        }

        $query = $this->createQuery($this->objectClass, $this->options['identifier'], $identifierValues);

        if (! $hydrate) {
            return $query->toArray();
        }

        return $query->find()->getArrayCopy();
    }

    /**
     * Create a query to use in the findByIdentifiers() method.
     *
     * @param string $class            Propel model class
     * @param string $identifierField  Identifier field name (e.g. "id")
     * @param array  $identifierValues Identifier values
     *
     * @return \ModelCriteria
     */
    protected function createQuery($class, $identifierField, array $identifierValues)
    {
        $queryClass = $class.'Query';
        $filterMethod = 'filterBy'.$this->camelize($identifierField);

        return $queryClass::create()->$filterMethod($identifierValues);
    }

    /**
     * @see https://github.com/doctrine/common/blob/master/lib/Doctrine/Common/Util/Inflector.php
     *
     * @param string $str
     *
     * @return string
     */
    private function camelize($str)
    {
        return ucfirst(str_replace(' ', '', ucwords(strtr($str, '_-', '  '))));
    }
}
