<?php

namespace Fazland\ElasticaBundle\Index;

use Fazland\ElasticaBundle\Elastica\Index;

class IndexManager
{
    /**
     * @var Index[]
     */
    private $indexes = [];

    /**
     * Gets all registered indexes.
     *
     * @return Index[]
     */
    public function getAllIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Gets an index by its name.
     *
     * @param string $name Index to return, or the default index if null
     *
     * @return Index
     *
     * @throws \InvalidArgumentException if no index exists for the given name
     */
    public function getIndex(string $name): Index
    {
        if (! isset($this->indexes[$name])) {
            throw new \InvalidArgumentException(sprintf('The index "%s" does not exist', $name));
        }

        return $this->indexes[$name];
    }

    /**
     * Adds an index to the manager.
     *
     * @param string $name
     * @param Index  $index
     */
    public function addIndex(string $name, Index $index)
    {
        $this->indexes[$name] = $index;
    }
}
