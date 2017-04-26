<?php

namespace Fazland\ElasticaBundle\Index\AliasStrategy;

use Elastica\Request;
use Elasticsearch\Endpoints\Indices\Alias\Get as GetAlias;
use Elasticsearch\Endpoints\Indices\Aliases\Update as UpdateAlias;
use Elasticsearch\Endpoints\Indices\Delete as DeleteIndex;
use Fazland\ElasticaBundle\Elastica\Index;

final class ReadWriteAliasStrategy implements IndexAwareAliasStrategyInterface
{
    /**
     * @var Index
     */
    private $index;

    /**
     * @var \Elastica\Client
     */
    private $client;

    /**
     * @param Index $index
     */
    public function setIndex(Index $index)
    {
        $this->index = $index;
        $this->client = $index->getClient();
    }

    public function buildName(string $originalName): string
    {
        return sprintf('%s_%s', $originalName, date('Y-m-d-His'));
    }

    public function getName(string $method, string $path): string
    {
        if (Request::GET === $method && preg_match('#/_search(/scroll)?$#i', $path)) {
            return $this->index->getName().'_read';
        }

        return $this->index->getName().'_write';
    }

    public function prePopulate()
    {
        // Do nothing
    }

    public function finalize()
    {
        $aliasName = $this->index->getAlias();

        $indexesAliased = $this->getAliasedIndex($aliasName);
        $this->updateAlias($aliasName, $indexesAliased);
        $this->deleteOldIndex($indexesAliased);
    }

    /**
     * @param $aliasName
     *
     * @return array
     */
    private function getAliasedIndex(string $aliasName): array
    {
        $get = new GetAlias();
        $get->setName($aliasName);

        $data = $this->client->requestEndpoint($get);
        $indexes = array_keys($data->getData());

        return $indexes;
    }

    /**
     * @param $aliasName
     * @param $indexesAliased
     */
    private function updateAlias(string $aliasName, array $indexesAliased)
    {
        $body = [];
        foreach ($indexesAliased as $index) {
            $body['actions'][] = ['remove' => [
                'index' => $index,
                'alias' => $aliasName,
            ]];
        }

        $body['actions'][] = ['add' => [
            'index' => $this->index->getName(),
            'alias' => $aliasName,
        ]];

        $update = new UpdateAlias();
        $update->setBody($body);

        $this->client->requestEndpoint($update);
    }

    /**
     * @param array $indexesAliased
     */
    private function deleteOldIndex(array $indexesAliased)
    {
        if (empty($indexesAliased) || count($indexesAliased) > 1) {
            return;
        }

        $delete = new DeleteIndex();
        $delete->setIndex(reset($indexesAliased));
        $this->client->requestEndpoint($delete);
    }
}
