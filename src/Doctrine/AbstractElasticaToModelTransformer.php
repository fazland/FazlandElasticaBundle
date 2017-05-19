<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Fazland\ElasticaBundle\Highlights\HighlightableInterface;
use Fazland\ElasticaBundle\Highlights\Highlighter;
use Fazland\ElasticaBundle\HybridResult;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformer as BaseTransformer;
use Fazland\ElasticaBundle\Transformer\ObjectFetcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps Elastica documents with Doctrine objects
 * This mapper assumes an exact match between
 * elastica documents ids and doctrine object ids.
 *
 * @deprecated
 */
abstract class AbstractElasticaToModelTransformer extends BaseTransformer implements ObjectFetcherInterface
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
     * PropertyAccessor instance.
     *
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * Instantiates a new Mapper.
     *
     * @param ManagerRegistry $registry
     * @param string          $objectClass
     * @param array           $options
     */
    public function __construct(ManagerRegistry $registry, $objectClass, array $options = [])
    {
        parent::__construct($options);

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->registry = $registry;
        $this->objectClass = $objectClass;
        $this->objectFetcher = $this;
        $this->highlighter = new Highlighter();
    }

    /**
     * Sets the PropertyAccessor instance.
     *
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
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
        foreach ($objects as $id => $object) {
            $id = $this->getIdentifierForObject($object);
            $result[$id] = new HybridResult($indexedElasticaResults[$id], $object);
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
     * {@inheritdoc}
     */
    public function setHighlights(array $objects, array $highlights)
    {
        foreach ($objects as $object) {
            if ($object instanceof HighlightableInterface) {
                $id = $this->getIdentifierForObject($object);
                $object->setElasticHighlights($highlights[$id]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find(...$identifiers)
    {
        $results = $this->findByIdentifiers($identifiers, $this->options['hydrate']);

        return iterator_to_array((function () use ($results) {
            foreach ($results as $object) {
                yield $this->getIdentifierForObject($object) => $object;
            }
        })());
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortingClosure(array $idPos, $identifierPath)
    {
        return function ($a, $b) use ($idPos) {
            return $idPos[$this->getIdentifierForObject($a)] > $idPos[$this->getIdentifierForObject($b)];
        };
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

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'hints' => [],
            'hydrate' => true,
            'query_builder_method' => 'createQueryBuilder',
        ]);
    }

    /**
     * Gets the identifier values for the given object.
     *
     * @param $object
     *
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
