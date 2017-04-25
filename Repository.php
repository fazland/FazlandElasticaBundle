<?php

namespace Fazland\ElasticaBundle;

use Fazland\ElasticaBundle\Finder\PaginatedFinderInterface;
use Fazland\ElasticaBundle\Manager\RepositoryManager;

/**
 * @author Richard Miller <info@limethinking.co.uk>
 *
 * Basic repository to be extended to hold custom queries to be run
 * in the finder.
 */
class Repository
{
    /**
     * @var RepositoryManager
     */
    protected $repositoryManager;

    /**
     * @var PaginatedFinderInterface
     */
    protected $finder;

    /**
     * @param RepositoryManager        $repositoryManager
     * @param PaginatedFinderInterface $finder
     */
    public function __construct(RepositoryManager $repositoryManager, PaginatedFinderInterface $finder)
    {
        $this->repositoryManager = $repositoryManager;
        $this->finder = $finder;
    }

    /**
     * @param mixed $query
     * @param int   $limit
     * @param array $options
     *
     * @return array
     */
    public function find($query, $limit = null, $options = [])
    {
        return $this->finder->find($query, $limit, $options);
    }

    /**
     * @param mixed $query
     * @param int   $limit
     * @param array $options
     *
     * @return mixed
     */
    public function findHybrid($query, $limit = null, $options = [])
    {
        return $this->finder->findHybrid($query, $limit, $options);
    }

    /**
     * @param mixed $query
     * @param array $options
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function findPaginated($query, $options = [])
    {
        return $this->finder->findPaginated($query, $options);
    }

    /**
     * @param string $query
     * @param array  $options
     *
     * @return Paginator\PaginatorAdapterInterface
     */
    public function createPaginatorAdapter($query, $options = [])
    {
        return $this->finder->createPaginatorAdapter($query, $options);
    }

    /**
     * @param mixed $query
     *
     * @return Paginator\HybridPaginatorAdapter
     */
    public function createHybridPaginatorAdapter($query)
    {
        return $this->finder->createHybridPaginatorAdapter($query);
    }
}
