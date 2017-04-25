<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\DependencyInjection\Config;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TypeConfig
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var IndexConfig
     */
    public $index;

    /**
     * @var string
     */
    public $service;

    /**
     * @var array
     */
    public $mapping;

    /**
     * @var array
     */
    public $config;

    /**
     * @var string
     */
    public $indexableCallback;

    /**
     * @var bool
     */
    private $persistenceIntegration = false;

    /**
     * @var string
     */
    public $persistenceDriver;

    /**
     * @var string
     */
    public $repository;

    /**
     * @var string
     */
    public $model;

    /**
     * @var string
     */
    public $modelIdentifier;

    /**
     * @var string
     */
    public $elasticaToModelTransformer;

    /**
     * @var array
     */
    public $elasticaToModelTransformerOptions = [];

    /**
     * @var string
     */
    public $modelToElasticaTransformer;

    /**
     * @var string
     */
    public $persister;

    /**
     * @var string
     */
    public $provider;

    /**
     * @var array
     */
    public $providerOptions;

    /**
     * @var string
     */
    public $listener;

    /**
     * @var array
     */
    public $listenerOptions;

    /**
     * @var string
     */
    public $finder;

    /**
     * @var array
     */
    public $serializerOptions;

    /**
     * @var Definition
     */
    public $configurationDefinition;

    public function __construct(string $name, IndexConfig $index, array $config)
    {
        $this->name = $name;
        $this->index = $index;
        $this->service = sprintf('%s.%s', $index->service, $this->name);
        $this->indexableCallback = $config['indexable_callback'] ?? null;

        $this->mapping = [];
        $this->config = [];
        $this->serializerOptions = $config['serializer'] ?? [];

        $this->loadMapping($config);
        $this->loadConfig($config);
    }

    public function getReference(): Reference
    {
        return new Reference($this->service);
    }

    public function hasPersistenceIntegration(): bool
    {
        return $this->persistenceIntegration;
    }

    private function loadMapping(array $config)
    {
        $fields = [
            'dynamic_templates',
            'properties',
            '_all',
            '_boost',
            '_id',
            '_parent',
            '_routing',
            '_source',
            '_timestamp',
            '_ttl',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $this->mapping[$field] = $config[$field];
            }
        }
    }

    private function loadConfig(array $config)
    {
        $fields = [
            'persistence',
            'serializer',
            'analyzer',
            'search_analyzer',
            'dynamic',
            'date_detection',
            'dynamic_date_formats',
            'numeric_detection',
            'stored_fields',
        ];

        foreach ($fields as $field) {
            $this->config[$field] = array_key_exists($field, $config) ? $config[$field] : null;
        }

        $this->loadPersistence($this->config);
    }

    private function loadPersistence(array $config)
    {
        if (empty($config['persistence'])) {
            return;
        }

        $config = $config['persistence'];
        $this->persistenceIntegration = true;

        $this->persistenceDriver = $config['driver'];
        $this->repository = $config['repository'] ?? null;
        $this->model = $config['model'] ?? null;
        $this->modelIdentifier = $config['identifier'] ?? null;

        if (isset($config['elastica_to_model_transformer']['service'])) {
            $this->elasticaToModelTransformer = $config['elastica_to_model_transformer']['service'];
        } elseif (! empty($config['elastica_to_model_transformer'])) {
            $this->elasticaToModelTransformerOptions = $config['elastica_to_model_transformer'];
        }

        if (isset($config['model_to_elastica_transformer']['service'])) {
            $this->modelToElasticaTransformer = $config['model_to_elastica_transformer']['service'];
        }

        $this->persister = $config['persister']['service'] ?? null;
        $this->finder = $config['finder']['service'] ?? null;

        if (isset($config['provider']['service'])) {
            $this->provider = $config['provider']['service'];
        } elseif (! empty($config['provider'])) {
            $this->provider = true;
            $this->providerOptions = $config['provider'];
        }

        if (isset($config['listener']['service'])) {
            $this->listener = $config['listener']['service'];
        } elseif (! empty($config['listener'])) {
            $this->listener = true;
            $this->listenerOptions = $config['listener'];
        }
    }
}
