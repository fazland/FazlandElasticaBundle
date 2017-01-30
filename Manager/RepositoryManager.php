<?php

namespace Fazland\ElasticaBundle\Manager;

use Fazland\ElasticaBundle\Finder\FinderInterface;
use Fazland\ElasticaBundle\Repository;
use RuntimeException;

/**
 * Allows retrieval of basic or custom repository for mapped Doctrine
 * entities/documents.
 */
class RepositoryManager implements RepositoryManagerInterface
{
    /**
     * @var array
     */
    private $types;

    /**
     * @var Repository[]
     */
    private $repositories;

    public function __construct()
    {
        $this->types = [];
        $this->repositories = [];
    }

    public function addType($indexTypeName, FinderInterface $finder, $repositoryName = null)
    {
        $this->types[$indexTypeName] = [
            'finder' => $finder,
            'repositoryName' => $repositoryName
        ];
    }

    /**
     * Return repository for entity.
     *
     * Returns custom repository if one specified otherwise
     * returns a basic repository.
     *
     * @param string $typeName
     *
     * @return Repository
     */
    public function getRepository($typeName)
    {
        if (isset($this->repositories[$typeName])) {
            return $this->repositories[$typeName];
        }

        if (! isset($this->types[$typeName])) {
            throw new RuntimeException(sprintf('No search finder configured for %s', $typeName));
        }

        $repository = $this->createRepository($typeName);
        $this->repositories[$typeName] = $repository;

        return $repository;
    }

    /**
     * @param $typeName
     *
     * @return string
     * @internal param string $entityName
     *
     */
    protected function getRepositoryName($typeName)
    {
        if (isset($this->types[$typeName]['repositoryName'])) {
            return $this->types[$typeName]['repositoryName'];
        }

        return Repository::class;
    }

    /**
     * @param $typeName
     *
     * @return mixed
     */
    private function createRepository($typeName)
    {
        if (! class_exists($repositoryName = $this->getRepositoryName($typeName))) {
            throw new RuntimeException(sprintf('%s repository for %s does not exist', $repositoryName, $typeName));
        }

        return new $repositoryName($this, $this->types[$typeName]['finder']);
    }
}
