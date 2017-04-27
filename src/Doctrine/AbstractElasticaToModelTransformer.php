<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Fazland\ElasticaBundle\HybridResult;
use Fazland\ElasticaBundle\Transformer\AbstractElasticaToModelTransformer as BaseTransformer;
use Fazland\ElasticaBundle\Transformer\HighlightableModelInterface;

/**
 * Maps Elastica documents with Doctrine objects
 * This mapper assumes an exact match between
 * elastica documents ids and doctrine object ids.
 */
abstract class AbstractElasticaToModelTransformer extends BaseTransformer
{
    /**
     * Manager registry.
     *
     * @var ManagerRegistry
     */
    protected $registry = null;

    /**
     * Class of the model to map to the elastica documents.
     *
     * @var string
     */
    protected $objectClass = null;

    /**
     * Optional parameters.
     *
     * @var array
     */
    protected $options = [
        'hints' => [],
        'hydrate' => true,
        'identifier' => null,
        'ignore_missing' => false,
        'query_builder_method' => 'createQueryBuilder',
    ];

    /**
     * Instantiates a new Mapper.
     *
     * @param ManagerRegistry $registry
     * @param string          $objectClass
     * @param array           $options
     */
    public function __construct(ManagerRegistry $registry, $objectClass, array $options = [])
    {
        $this->registry = $registry;
        $this->objectClass = $objectClass;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Returns the object class that is used for conversion.
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Transforms an array of elastica objects into an array of
     * model objects fetched from the doctrine repository.
     *
     * @param array $elasticaObjects of elastica objects
     *
     * @throws \RuntimeException
     *
     * @return array
     **/
    public function transform(array $elasticaObjects)
    {
        $ids = $highlights = [];
        foreach ($elasticaObjects as $elasticaObject) {
            $ids[] = $elasticaObject->getId();
            $highlights[$elasticaObject->getId()] = $elasticaObject->getHighlights();
        }

        $objects = $this->findByIdentifiers($ids, $this->options['hydrate']);
        $objectsCnt = count($objects);
        $elasticaObjectsCnt = count($elasticaObjects);
        if (! $this->options['ignore_missing'] && $objectsCnt < $elasticaObjectsCnt) {
            throw new \RuntimeException(sprintf('Cannot find corresponding Doctrine objects (%d) for all Elastica results (%d). IDs: %s', $objectsCnt, $elasticaObjectsCnt, join(', ', $ids)));
        }

        foreach ($objects as $object) {
            if ($object instanceof HighlightableModelInterface) {
                $id = $this->getIdentifierForObject($object);
                $object->setElasticHighlights($highlights[$id]);
            }
        }

        // sort objects in the order of ids
        $idPos = array_flip($ids);
        usort(
            $objects,
            function ($a, $b) use ($idPos) {
                $idA = $this->getIdentifierForObject($a);
                $idB = $this->getIdentifierForObject($b);

                return $idPos[$idA] > $idPos[$idB];
            }
        );

        return $objects;
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

        $indexedElasticaResults = [];
        foreach ($elasticaObjects as $elasticaObject) {
            $indexedElasticaResults[(string) $elasticaObject->getId()] = $elasticaObject;
        }

        $objects = $this->transform($elasticaObjects);

        $result = [];
        foreach ($objects as $object) {
            $id = $this->getIdentifierForObject($object);
            $result[] = new HybridResult($indexedElasticaResults[$id], $object);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierField()
    {
        if ($this->options['identifier']) {
            return $this->options['identifier'];
        }

        return $this->registry
            ->getManagerForClass($this->objectClass)
            ->getClassMetadata($this->objectClass)
            ->getIdentifier();
    }

    /**
     * Fetches objects by theses identifier values.
     *
     * @param array $identifierValues ids values
     * @param bool  $hydrate          whether or not to hydrate the objects, false returns arrays
     *
     * @return array of objects or arrays
     */
    abstract protected function findByIdentifiers(array $identifierValues, $hydrate);

    /**
     * Gets the identifier values for the given object.
     *
     * @param $object
     * @return string
     */
    protected function getIdentifierForObject($object): string
    {
        $id = array_map(function ($field) use ($object) {
            $field = is_array($object) ? '['.$field.']' : $field;
            return $this->propertyAccessor->getValue($object, $field);
        }, (array) $this->getIdentifierField());

        return implode(' ', $id);
    }
}
