<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\DependencyInjection\Config;

use Symfony\Component\DependencyInjection\Reference;

class IndexConfig
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $indexName;

    /**
     * @var string
     */
    public $client;

    /**
     * @var array
     */
    public $settings;

    /**
     * @var array
     */
    public $typePrototype;

    /**
     * @var bool
     */
    public $useAlias;

    /**
     * @var string
     */
    public $service;

    /**
     * @var TypeConfig[]
     */
    public $types;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->indexName = $config['index_name'] ?? $name;
        $this->client = $config['client'] ?? null;
        $this->settings = $config['settings'] ?? [];
        $this->typePrototype = $config['type_prototype'] ?? [];
        $this->useAlias = $config['use_alias'];
        $this->service = sprintf('fazland_elastica.index.%s', $name);
        $this->types = [];

        foreach ($config['types'] as $typeName => $typeConfig) {
            $this->types[$typeName] = new TypeConfig($typeName, $this, $typeConfig);
        }
    }

    public function getReference(): Reference
    {
        return new Reference($this->service);
    }
}
