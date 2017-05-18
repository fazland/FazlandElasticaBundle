<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Fazland\ElasticaBundle\Transformer\ObjectFetcherInterface;

class ObjectFetcher implements ObjectFetcherInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var string
     */
    private $objectClass;

    public function __construct(ManagerRegistry $doctrine, string $objectClass)
    {
        $this->doctrine = $doctrine;
        $this->objectClass = $objectClass;
    }

    /**
     * @inheritDoc
     */
    public function find(...$identifiers)
    {
        $results = [];
        $manager = $this->doctrine->getManagerForClass($this->objectClass);

        foreach ($identifiers as $identifier) {
            $identifier = explode(' ', $identifier);
            if (1 === count($identifier)) {
                $identifier = reset($identifier);
            }

            $results[$identifier] = $manager->find($this->objectClass, $identifier);
        }

        return $results;
    }
}
