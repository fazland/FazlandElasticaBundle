<?php

namespace Fazland\ElasticaBundle\Doctrine\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ElasticaBundle\Doctrine\AbstractProvider;

class Provider extends AbstractProvider
{
    public function count(int $offset = null, int $size = null)
    {
        /** @var Builder $qb */
        $qb = $this->createQueryBuilder($this->options['query_builder_method']);

        if (null !== $offset) {
            $qb->skip($offset);
        }

        if (null !== $size) {
            $qb->limit($size);
        }

        return $qb
            ->getQuery()
            ->count();
    }

    public function provide(int $offset = null, int $size = null)
    {
        /** @var Builder $qb */
        $qb = $this->createQueryBuilder($this->options['query_builder_method']);

        if (null !== $offset) {
            $qb->skip($offset);
        }

        if (null !== $size) {
            $qb->limit($size);
        }

        return $qb->getQuery();
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
