<?php

namespace Fazland\ElasticaBundle\Doctrine\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ElasticaBundle\Doctrine\SliceFetcherInterface;
use Fazland\ElasticaBundle\Exception\InvalidArgumentTypeException;

/**
 * Fetches a slice of objects.
 *
 * @author Thomas Prelot <tprelot@gmail.com>
 */
class SliceFetcher implements SliceFetcherInterface
{
    /**
     * {@inheritdoc}
     */
    public function fetch($queryBuilder, $limit, $offset, array $previousSlice, array $identifierFieldNames)
    {
        if (! $queryBuilder instanceof Builder) {
            throw new InvalidArgumentTypeException($queryBuilder, 'Doctrine\ODM\MongoDB\Query\Builder');
        }

        $lastObject = array_pop($previousSlice);

        if ($lastObject) {
            $queryBuilder
                ->field('_id')->gt($lastObject->getId())
                ->skip(0)
            ;
        } else {
            $queryBuilder->skip($offset);
        }

        return $queryBuilder
            ->limit($limit)
            ->sort(['_id' => 'asc'])
            ->getQuery()
            ->execute()
            ->toArray()
        ;
    }
}
