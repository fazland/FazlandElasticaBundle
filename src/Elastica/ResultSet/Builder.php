<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica\ResultSet;

use Elastica\Query;
use Elastica\Response;
use Elastica\Result;
use Elastica\ResultSet\BuilderInterface;
use Fazland\ElasticaBundle\Elastica\ResultSet;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;

class Builder implements BuilderInterface
{
    /**
     * @var ElasticaToModelTransformerInterface
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    public function buildResultSet(Response $response, Query $query)
    {
        $results = $this->buildResults($response);
        $resultSet = new ResultSet($response, $query, $results);

        if (null !== $this->transformer) {
            $resultSet->setTransfomer($this->transformer);
        }

        return $resultSet;
    }

    /**
     * @param ElasticaToModelTransformerInterface $transformer
     */
    public function setTransformer(ElasticaToModelTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    private function buildResults(Response $response)
    {
        $data = $response->getData();
        $results = [];

        if (! isset($data['hits']['hits'])) {
            return $results;
        }

        foreach ($data['hits']['hits'] as $hit) {
            $results[] = new Result($hit);
        }

        return $results;
    }
}
