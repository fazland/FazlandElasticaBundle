<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\DependencyInjection\Config;

use Symfony\Component\DependencyInjection\Definition;
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
     * @var string
     */
    public $alias;

    /**
     * @var string
     */
    public $service;

    /**
     * @var TypeConfig[]
     */
    public $types;

    /**
     * @var Definition
     */
    public $configurationDefinition;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->indexName = $config['index_name'] ?? $name;
        $this->client = $config['client'] ?? null;
        $this->settings = $config['settings'] ?? [];
        $this->typePrototype = $config['type_prototype'] ?? [];
        $this->alias = $config['use_alias'];
        $this->service = sprintf('fazland_elastica.index.%s', $name);
        $this->types = [];

        foreach ($config['types'] as $typeName => $typeConfig) {
            $this->types[$typeName] = new TypeConfig($typeName, $this, $typeConfig);
        }

        $this->buildConfigDefinition();
    }

    public function getReference(): Reference
    {
        return new Reference($this->service);
    }

    private function buildConfigDefinition()
    {
        $types = [];
        foreach ($this->types as $typeConfig) {
            $typeDef = new Definition(\Fazland\ElasticaBundle\Configuration\TypeConfig::class);
            $typeDef->setArguments([
                $typeConfig->name,
                $typeConfig->mapping,
                $typeConfig->config,
            ]);

            $types[$typeConfig->name] = $typeDef;
            $typeConfig->configurationDefinition = $typeDef;
        }

        $indexConfigDef = new Definition(\Fazland\ElasticaBundle\Configuration\IndexConfig::class);
        $indexConfigDef->setArguments([
            $this->name,
            $types,
            [
                'elasticSearchName' => $this->indexName,
                'settings' => $this->settings,
                'aliasStrategy' => $this->alias,
            ],
        ]);

        $this->configurationDefinition = $indexConfigDef;
    }
}
