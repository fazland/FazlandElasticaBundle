<?php

namespace Fazland\ElasticaBundle\Transformer\Doctrine;

use Elastica\Document;

class ModelToElasticaIdentifierTransformer extends ModelToElasticaAutoTransformer
{
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
        return new Document($this->getIdentifier($object));
    }
}
