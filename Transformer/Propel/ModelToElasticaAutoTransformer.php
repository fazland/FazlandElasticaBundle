<?php

namespace Fazland\ElasticaBundle\Transformer\Propel;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Fazland\ElasticaBundle\Exception\IdentifierNotFoundException;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaAutoTransformer as BaseTransformer;

class ModelToElasticaAutoTransformer extends BaseTransformer
{
    /**
     * @inheritDoc
     */
    protected function getIdentifier($object)
    {
        try {
            return parent::getIdentifier($object);
        } catch (IdentifierNotFoundException $e) {
            throw new \Exception('Unimplemented'); // TODO
        }
    }
}
