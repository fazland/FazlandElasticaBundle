<?php

namespace Fazland\ElasticaBundle\Doctrine\ORM;

use Doctrine\ORM\Query;
use Fazland\ElasticaBundle\Doctrine\AbstractElasticaToModelTransformer;

/**
 * Maps Elastica documents with Doctrine objects
 * This mapper assumes an exact match between
 * elastica documents ids and doctrine object ids.
 */
class ElasticaToModelTransformer extends AbstractElasticaToModelTransformer
{
    const ENTITY_ALIAS = 'o';

    /**
     * Fetch objects for theses identifier values.
     *
     * @param array $identifierValues ids values
     * @param bool  $hydrate          whether or not to hydrate the objects, false returns arrays
     *
     * @return array of objects or arrays
     */
    protected function findByIdentifiers(array $identifierValues, $hydrate)
    {
        if (empty($identifierValues)) {
            return [];
        }

        $hydrationMode = $hydrate ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $identifierFields = $this->getIdentifierFields();

        $qb = $this->getEntityQueryBuilder();
        if (count($identifierFields) === 1) {
            $qb->andWhere($qb->expr()->in(static::ENTITY_ALIAS.'.'.$identifierFields[0], ':values'))
                ->setParameter('values', $identifierValues);
        } else {
            $conditions = [];
            $counter = 0;

            foreach ($identifierValues as $value) {
                $keys = explode(' ', $value, count($identifierFields));
                $idCondition = [];

                foreach ($identifierFields as $i => $field) {
                    $idCondition[] = $qb->expr()->eq(static::ENTITY_ALIAS.'.'.$field, ':param_'.++$counter);
                    $qb->setParameter('param_'.$counter, $keys[$i]);
                }

                $conditions[] = $qb->expr()->andX(...$idCondition);
            }

            $qb->andWhere($qb->expr()->orX(...$conditions));
        }

        $query = $qb->getQuery();

        foreach ($this->options['hints'] as $hint) {
            $query->setHint($hint['name'], $hint['value']);
        }

        return $query->setHydrationMode($hydrationMode)->execute();
    }

    /**
     * Retrieves a query builder to be used for querying by identifiers.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getEntityQueryBuilder()
    {
        $repository = $this->registry
            ->getManagerForClass($this->objectClass)
            ->getRepository($this->objectClass);

        return $repository->{$this->options['query_builder_method']}(static::ENTITY_ALIAS);
    }

    protected function getIdentifierFields(): array
    {
        if (! isset($this->options['identifier'])) {
            $manager = $this->registry->getManagerForClass($this->objectClass);
            $metadata = $manager->getClassMetadata($this->objectClass);

            $identifier = $metadata->getIdentifier();
        } else {
            $identifier = $this->options['identifier'];
            if (! is_array($identifier)) {
                $identifier = [$identifier];
            }
        }

        return array_values($identifier);
    }
}
