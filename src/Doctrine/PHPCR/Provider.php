<?php

namespace Fazland\ElasticaBundle\Doctrine\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Query;
use Fazland\ElasticaBundle\Doctrine\AbstractProvider;

class Provider extends AbstractProvider
{
    const ENTITY_ALIAS = 'a';

    public function count(int $offset = null, int $size = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder($this->options['query_builder_method']);

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        if (null !== $size) {
            $qb->setMaxResults($size);
        }

        return $qb
            ->getQuery()
            ->execute(null, Query::HYDRATE_PHPCR)
            ->getRows()
            ->count();
    }

    public function provide(int $offset = null, int $size = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder($this->options['query_builder_method']);

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        if (null !== $size) {
            $qb->setMaxResults($size);
        }

        return $qb->getQuery()->getResult();
    }

    public function clear()
    {
        // Do nothing.
        // Clearing PHPCR on each cycle will break up references in uninitialized proxies
        // and the Query `iterate` method has been not implemented as of PHPCR-ODM v1.4.2
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($method, array $arguments = [])
    {
        $repository = $this->managerRegistry
            ->getRepository($this->modelClass);

        // PHPCR query builders require an alias argument
        $arguments = [static::ENTITY_ALIAS] + $arguments;

        return $repository->{$method}(...$arguments);
    }
}
