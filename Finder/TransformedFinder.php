<?php

namespace Fazland\ElasticaBundle\Finder;

use Elastica\Document;
use Elastica\Query;
use Elastica\SearchableInterface;
use Fazland\ElasticaBundle\Paginator\FantaPaginatorAdapter;
use Fazland\ElasticaBundle\Paginator\HybridPaginatorAdapter;
use Fazland\ElasticaBundle\Paginator\TransformedPaginatorAdapter;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use Pagerfanta\Pagerfanta;

/**
 * Finds elastica documents and map them to persisted objects.
 */
class TransformedFinder implements PaginatedFinderInterface
{
    protected $searchable;
    protected $transformer;

    /**
     * @param SearchableInterface $searchable
     * @param ElasticaToModelTransformerInterface $transformer
     */
    public function __construct(SearchableInterface $searchable, ElasticaToModelTransformerInterface $transformer)
    {
        $this->searchable  = $searchable;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $limit = null, $options = [])
    {
        $results = $this->search($query, $limit, $options);

        return $this->transformer->transform($results);
    }

    public function findHybrid($query, $limit = null, $options = [])
    {
        $results = $this->search($query, $limit, $options);

        return $this->transformer->hybridTransform($results);
    }

    /**
     * Find documents similar to one with passed id.
     *
     * @param integer $id
     * @param array   $params
     * @param array   $query
     *
     * @return array of model objects
     **/
    public function moreLikeThis($id, $params = [], $query = [])
    {
        $doc = new Document($id);
        $results = $this->searchable->moreLikeThis($doc, $params, $query)->getResults();

        return $this->transformer->transform($results);
    }

    /**
     * @param $query
     * @param null|int $limit
     * @param array    $options
     *
     * @return array
     */
    protected function search($query, $limit = null, $options = [])
    {
        $queryObject = Query::create($query);
        if (null !== $limit) {
            $queryObject->setSize($limit);
        }
        $results = $this->searchable->search($queryObject, $options)->getResults();

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findPaginated($query, $options = [])
    {
        $queryObject = Query::create($query);
        $paginatorAdapter = $this->createPaginatorAdapter($queryObject, $options);

        return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
    }

    /**
     * {@inheritdoc}
     */
    public function createPaginatorAdapter($query, $options = [])
    {
        $query = Query::create($query);

        return new TransformedPaginatorAdapter($this->searchable, $query, $options, $this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function createHybridPaginatorAdapter($query)
    {
        $query = Query::create($query);

        return new HybridPaginatorAdapter($this->searchable, $query, $this->transformer);
    }
}
