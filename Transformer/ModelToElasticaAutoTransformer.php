<?php

namespace Fazland\ElasticaBundle\Transformer;

use Elastica\Document;
use Elastica\Type;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\TransformEvent;
use Fazland\ElasticaBundle\Exception\IdentifierNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps Elastica documents with Doctrine objects
 * This mapper assumes an exact match between
 * elastica documents ids and doctrine object ids.
 */
class ModelToElasticaAutoTransformer implements ModelToElasticaTransformerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Optional parameters.
     *
     * @var array
     */
    protected $options = [];

    /**
     * PropertyAccessor instance.
     *
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var Type
     */
    protected $type;

    /**
     * Instanciates a new Mapper.
     *
     * @param array $options
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(array $options = [], EventDispatcherInterface $dispatcher = null)
    {
        $this->options = array_merge($this->options, $options);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set the PropertyAccessor.
     *
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param Type $type
     */
    public function setType(Type $type)
    {
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function transform($object, array $mapping)
    {
        $fields = $mapping['properties'] ?? [];
        if (isset($mapping['_parent'])) {
            $fields['_parent'] = $mapping['_parent'];
        }

        $document = $this->transformObjectToDocument($object, $fields, $this->getIdentifier($object));

        return $document;
    }

    /**
     * Gets an identifier string for the given object
     *
     * @param object $object
     *
     * @return string
     *
     * @throws IdentifierNotFoundException
     */
    protected function getIdentifier($object)
    {
        if (isset($this->options['identifier'])) {
            $fields = (array)$this->options['identifier'];
            $identifier = array_map(function (string $field) use ($object) {
                return $this->propertyAccessor->getValue($object, $field);
            }, $fields);

            return implode(' ', $identifier);
        }

        throw new IdentifierNotFoundException('Cannot retrieve an identifier for object');
    }

    /**
     * transform a nested document or an object property into an array of ElasticaDocument.
     *
     * @param array|\Traversable|\ArrayAccess $objects the object to convert
     * @param array                           $fields  the keys we want to have in the returned array
     *
     * @return array
     */
    protected function transformNested($objects, array $fields)
    {
        if (is_array($objects) || $objects instanceof \Traversable || $objects instanceof \ArrayAccess) {
            $documents = [];
            foreach ($objects as $object) {
                $document = $this->transformObjectToDocument($object, $fields);
                $documents[] = $document->getData();
            }

            return $documents;
        } elseif (null !== $objects) {
            $document = $this->transformObjectToDocument($objects, $fields);

            return $document->getData();
        }

        return [];
    }

    /**
     * Attempts to convert any type to a string or an array of strings.
     *
     * @param mixed $value
     *
     * @return string|array
     */
    protected function normalizeValue($value)
    {
        $normalizeValue = function (&$v) {
            if ($v instanceof \DateTime) {
                $v = $v->format('c');
            } elseif (! is_scalar($v) && ! is_null($v)) {
                $v = (string) $v;
            }
        };

        if (is_array($value) || $value instanceof \Traversable || $value instanceof \ArrayAccess) {
            $value = is_array($value) ? $value : iterator_to_array($value, false);
            array_walk_recursive($value, $normalizeValue);
        } else {
            $normalizeValue($value);
        }

        return $value;
    }

    /**
     * Transforms the given object to an elastica document
     *
     * @param object $object the object to convert
     * @param array  $fields the keys we want to have in the returned array
     * @param string $identifier the identifier for the new document
     * @return Document
     */
    protected function transformObjectToDocument($object, array $fields, $identifier = '')
    {
        $document = new Document($identifier);

        if ($this->dispatcher) {
            $event = new TransformEvent($document, $fields, $object, $this->type);
            $this->dispatcher->dispatch(Events::PRE_TRANSFORM, $event);

            $document = $event->getDocument();
        }

        foreach ($fields as $key => $mapping) {
            if ($key == '_parent') {
                $property = (null !== $mapping['property']) ? $mapping['property'] : $mapping['type'];
                $value = $this->propertyAccessor->getValue($object, $property);

                $parentIdentifier = implode(' ', array_map(function ($field) use ($value) {
                    return $this->propertyAccessor->getValue($value, $field);
                }, (array)$mapping['identifier']));
                $document->setParent($parentIdentifier);

                continue;
            }

            $path = isset($mapping['property_path']) ?
                $mapping['property_path'] :
                $key;
            if (false === $path) {
                continue;
            }
            $value = $this->propertyAccessor->getValue($object, $path);

            if (isset($mapping['type']) && in_array(
                    $mapping['type'], ['nested', 'object']
                ) && isset($mapping['properties']) && ! empty($mapping['properties'])
            ) {
                /* $value is a nested document or object. Transform $value into
                 * an array of documents, respective the mapped properties.
                 */
                $document->set($key, $this->transformNested($value, $mapping['properties']));

                continue;
            }

            if (isset($mapping['type']) && $mapping['type'] == 'attachment') {
                // $value is an attachment. Add it to the document.
                if ($value instanceof \SplFileInfo) {
                    $document->addFile($key, $value->getPathName());
                } else {
                    $document->addFileContent($key, $value);
                }

                continue;
            }

            $document->set($key, $this->normalizeValue($value));
        }

        if ($this->dispatcher) {
            $event = new TransformEvent($document, $fields, $object, $this->type);
            $this->dispatcher->dispatch(Events::POST_TRANSFORM, $event);

            $document = $event->getDocument();
        }

        return $document;
    }
}
