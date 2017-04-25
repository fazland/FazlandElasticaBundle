<?php

namespace Fazland\ElasticaBundle\Doctrine\PHPCR;

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

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($method, array $arguments = [])
    {
        $repository = $this->managerRegistry
            ->getRepository($this->modelClass);

        return $repository->{$method}(...$arguments);
    }
}
