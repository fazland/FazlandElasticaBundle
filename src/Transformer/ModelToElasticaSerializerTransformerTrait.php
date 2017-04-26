<?php

namespace Fazland\ElasticaBundle\Transformer;

use Elastica\Document;

trait ModelToElasticaSerializerTransformerTrait
{
    /**
     * @var callable
     */
    protected $serializerCallback;

    /**
     * Sets the serializer callback for the current transformer.
     *
     * @param callable $callback
     */
    public function setSerializerCallback(callable $callback)
    {
        $this->serializerCallback = $callback;
    }

    /**
     * Creates an elastica document with the id of the doctrine object as id.
     *
     * @param object $object the object to convert
     * @param array  $fields the keys we want to have in the returned array
     *
     * @return Document
     **/
    public function transform($object, array $fields)
    {
        $doc = new Document($this->getIdentifier($object));
        $doc->setData(call_user_func($this->serializerCallback, $object));

        return $doc;
    }
}
