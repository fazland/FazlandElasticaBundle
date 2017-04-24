<?php

namespace Fazland\ElasticaBundle\Transformer\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Fazland\ElasticaBundle\Exception\IdentifierNotFoundException;
use Fazland\ElasticaBundle\Transformer\ModelToElasticaAutoTransformer as BaseTransformer;

class ModelToElasticaAutoTransformer extends BaseTransformer
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function setDoctrine(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritDoc
     */
    protected function getIdentifier($object)
    {
        try {
            return parent::getIdentifier($object);
        } catch (IdentifierNotFoundException $e) {
            // Try to resolve with doctrine's metadata
            $manager = $this->doctrine->getManagerForClass($class = ClassUtils::getClass($object));
            $metadata = $manager->getClassMetadata($class);

            return $metadata->getIdentifierValues($object);
        }
    }
}
