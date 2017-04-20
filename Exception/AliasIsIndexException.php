<?php

namespace Fazland\ElasticaBundle\Exception;

class AliasIsIndexException extends \Exception implements ExceptionInterface
{
    /**
     * @param string $indexName
     */
    public function __construct($indexName)
    {
        parent::__construct(sprintf('Expected %s to be an alias but it is an index.', $indexName));
    }
}
