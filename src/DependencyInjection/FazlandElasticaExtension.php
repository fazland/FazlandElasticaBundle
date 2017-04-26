<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\DependencyInjection;

use Doctrine\ODM\MongoDB\Events as MongoDBEvents;
use Doctrine\ODM\PHPCR\Event as PHPCREvents;
use Doctrine\ORM\Events as ORMEvents;
use Fazland\ElasticaBundle\DependencyInjection\Config\IndexConfig;
use Fazland\ElasticaBundle\DependencyInjection\Config\TypeConfig;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FazlandElasticaExtension extends Extension
{
    /**
     * Definition of elastica clients as configured by this extension.
     *
     * @var array
     */
    private $clients = [];

    /**
     * An array of indexes as configured by the extension.
     *
     * @var IndexConfig[]
     */
    private $indexConfigs = [];

    /**
     * If we've encountered a type mapped to a specific persistence driver, it will be loaded
     * here.
     *
     * @var array
     */
    private $loadedDrivers = [];

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (empty($config['clients']) || empty($config['indexes'])) {
            // No Clients or indexes are defined
            return;
        }

        foreach (['config', 'commands', 'index', 'persister', 'provider', 'transformer'] as $basename) {
            $loader->load(sprintf('%s.xml', $basename));
        }

        if (empty($config['default_client'])) {
            $keys = array_keys($config['clients']);
            $config['default_client'] = reset($keys);
        }

        if (isset($config['serializer'])) {
            $loader->load('serializer.xml');

            $this->loadSerializer($config['serializer'], $container);
        }

        $this->loadClients($config['clients'], $container);
        $container->setAlias('fazland_elastica.client', sprintf('fazland_elastica.client.%s', $config['default_client']));

        $this->loadIndexes($config['indexes'], $container);
        $this->loadIndexManager($container);

        $this->loadCaches($config['cache'], $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * Loads the configured clients.
     *
     * @param array            $clients   An array of clients configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    private function loadClients(array $clients, ContainerBuilder $container)
    {
        foreach ($clients as $name => $clientConfig) {
            $clientId = sprintf('fazland_elastica.client.%s', $name);

            $clientDef = new DefinitionDecorator('fazland_elastica.client_prototype');
            $clientDef->replaceArgument(0, $clientConfig);

            $logger = $clientConfig['connections'][0]['logger'];
            if (false !== $logger) {
                $clientDef->addMethodCall('setLogger', [new Reference($logger)]);
            }

            $clientDef->addTag('fazland_elastica.client');

            $container->setDefinition($clientId, $clientDef);

            $this->clients[$name] = [
                'id' => $clientId,
                'reference' => new Reference($clientId),
            ];
        }
    }

    /**
     * Loads the configured indexes.
     *
     * @param array            $indexes   An array of indexes configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException
     */
    private function loadIndexes(array $indexes, ContainerBuilder $container)
    {
        foreach ($indexes as $name => $index) {
            $indexConfig = new IndexConfig($name, $index);
            $this->indexConfigs[$name] = $indexConfig;
        }

        $this->resolveMappingReferences();

        foreach ($this->indexConfigs as $name => $indexConfig) {
            $indexConfig->buildConfigDefinition();
            $indexDef = new DefinitionDecorator('fazland_elastica.index_prototype');
            $indexDef->replaceArgument(0, $this->getClient($indexConfig->client));
            $indexDef->replaceArgument(1, $indexConfig->configurationDefinition);

            $container->setDefinition($indexConfig->service, $indexDef);

            if ($indexConfig->alias) {
                $this->addIndexAliasStrategy($indexConfig, $container);
            }

            $this->addIndexToClient($indexConfig, $container);

            if ($indexConfig->finder) {
                $this->loadIndexFinder($indexConfig, $container);
            }

            foreach ($indexConfig->types as $type) {
                $this->loadType($type, $container);
            }
        }
    }

    /**
     * Loads the configured index finders.
     *
     * @param IndexConfig      $indexConfig
     * @param ContainerBuilder $container
     */
    private function loadIndexFinder(IndexConfig $indexConfig, ContainerBuilder $container)
    {
        /*
         * Note: transformer services may conflict with "collection.index", if
         * an index and type names were "collection" and an index, respectively.
         */

        $transformerId = sprintf('fazland_elastica.elastica_to_model_transformer.collection.%s', $indexConfig->name);
        $transformerDef = new DefinitionDecorator('fazland_elastica.elastica_to_model_transformer.collection');
        $container->setDefinition($transformerId, $transformerDef);

        $finderId = sprintf('fazland_elastica.finder.%s', $indexConfig->name);

        $finderDef = new DefinitionDecorator('fazland_elastica.finder');
        $finderDef->replaceArgument(0, $indexConfig->getReference());
        $finderDef->replaceArgument(1, new Reference($transformerId));

        $container->setDefinition($finderId, $finderDef);
    }

    /**
     * Loads the configured type.
     *
     * @param TypeConfig       $type
     * @param ContainerBuilder $container
     */
    private function loadType(TypeConfig $type, ContainerBuilder $container)
    {
        $indexConfig = $type->index;

        $typeDef = new DefinitionDecorator('fazland_elastica.type_prototype');
        $typeDef->replaceArgument(0, $type->index->getReference());
        $typeDef->replaceArgument(1, $type->configurationDefinition);

        $indexDef = $container->findDefinition($type->index->service);
        $indexDef->addMethodCall('registerType', [$type->name, $type->service]);

        $container->setDefinition($type->service, $typeDef);

        if ($type->indexableCallback) {
            $container->getDefinition('fazland_elastica.indexable.default')
                ->addMethodCall('addCallback', [sprintf('%s/%s', $indexConfig->name, $type->name), $type->indexableCallback]);
        }

        if ($type->hasPersistenceIntegration()) {
            $this->loadTypePersistenceIntegration($type, $container);
        }

        if ($type->provider) {
            $typeDef->addMethodCall('setProvider', [new Reference($type->provider)]);
        }

        if ($type->modelToElasticaTransformer) {
            $typeDef->addMethodCall('setModelTransformer', [new Reference($type->modelToElasticaTransformer)]);
        }

        if ($type->elasticaToModelTransformer) {
            $typeDef->addMethodCall('setElasticaTransformer', [new Reference($type->elasticaToModelTransformer)]);
        }
    }

    /**
     * Loads the optional provider and finder for a type.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadTypePersistenceIntegration(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if ($typeConfig->persistenceDriver) {
            $this->loadDriver($container, $typeConfig->persistenceDriver);

            $this->loadElasticaToModelTransformer($typeConfig, $container);
            $this->loadModelToElasticaTransformer($typeConfig, $container);
            $this->loadObjectPersister($typeConfig, $container);
        }

        if ($typeConfig->provider) {
            $this->loadTypeProvider($typeConfig, $container);
        }

        $this->loadTypeFinder($typeConfig, $container);

        if ($typeConfig->listener) {
            $this->loadTypeListener($typeConfig, $container);
        }
    }

    /**
     * Creates and loads an ElasticaToModelTransformer.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadElasticaToModelTransformer(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null !== $typeConfig->elasticaToModelTransformer) {
            return;
        }

        /*
         * Note: transformer services may conflict with "prototype.driver", if
         * the index and type names were "prototype" and a driver, respectively.
         */
        $abstractId = sprintf('fazland_elastica.elastica_to_model_transformer.prototype.%s', $typeConfig->persistenceDriver);
        $serviceId = sprintf('fazland_elastica.elastica_to_model_transformer.%s.%s', $typeConfig->index->name, $typeConfig->name);
        $serviceDef = new DefinitionDecorator($abstractId);
        $serviceDef->addTag('fazland_elastica.elastica_to_model_transformer', ['type' => $typeConfig->name, 'index' => $typeConfig->index->name]);

        // Doctrine has a mandatory service as first argument
        $argPos = ('propel' === $typeConfig->persistenceDriver) ? 0 : 1;

        $serviceDef->replaceArgument($argPos, $typeConfig->model);
        $serviceDef->replaceArgument($argPos + 1, array_merge($typeConfig->elasticaToModelTransformerOptions, [
            'identifier' => $typeConfig->modelIdentifier,
        ]));

        $container->setDefinition($serviceId, $serviceDef);
        $typeConfig->elasticaToModelTransformer = $serviceId;
    }

    /**
     * Creates and loads a ModelToElasticaTransformer for an index/type.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadModelToElasticaTransformer(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null !== $typeConfig->modelToElasticaTransformer) {
            return;
        }

        if (in_array($typeConfig->persistenceDriver, ['orm', 'mongodb', 'phpcr'])) {
            $prefix = 'fazland_elastica.doctrine.';
        } else {
            $prefix = 'fazland_elastica.propel.';
        }

        $abstractId = $prefix.($container->hasDefinition('fazland_elastica.serializer_callback_prototype') ?
            'model_to_elastica_identifier_transformer' : 'model_to_elastica_transformer');

        $serviceId = sprintf('fazland_elastica.model_to_elastica_transformer.%s.%s', $typeConfig->index->name, $typeConfig->name);
        $serviceDef = new DefinitionDecorator($abstractId);
        $serviceDef->addMethodCall('setType', [$typeConfig->getReference()]);
        $serviceDef->replaceArgument(0, [
            'identifier' => $typeConfig->modelIdentifier,
        ]);

        if ($typeConfig->persistenceDriver === 'orm') {
            $serviceDef->addMethodCall('setDoctrine', [new Reference('doctrine')]);
        } elseif ($typeConfig->persistenceDriver === 'mongodb') {
            $serviceDef->addMethodCall('setDoctrine', [new Reference('doctrine_mongodb')]);
        } elseif ($typeConfig->persistenceDriver === 'phpcr') {
            $serviceDef->addMethodCall('setDoctrine', [new Reference('doctrine_phpcr')]);
        }

        if ($container->hasDefinition('fazland_elastica.serializer_callback_prototype')) {
            $typeSerializerId = sprintf('%s.serializer.callback', $typeConfig->service);
            $typeSerializerDef = new DefinitionDecorator('fazland_elastica.serializer_callback_prototype');

            if (isset($typeConfig->serializerOptions['groups'])) {
                $typeSerializerDef->addMethodCall('setGroups', [$typeConfig->serializerOptions['groups']]);
            }

            if (isset($typeConfig->serializerOptions['serialize_null'])) {
                $typeSerializerDef->addMethodCall('setSerializeNull', [$typeConfig->serializerOptions['serialize_null']]);
            }

            if (isset($typeConfig->serializerOptions['version'])) {
                $typeSerializerDef->addMethodCall('setVersion', [$typeConfig->serializerOptions['version']]);
            }

            $serviceDef->addMethodCall('setSerializerCallback', [[new Reference($typeSerializerId), 'serialize']]);
            $container->setDefinition($typeSerializerId, $typeSerializerDef);
        }

        $container->setDefinition($serviceId, $serviceDef);
        $typeConfig->modelToElasticaTransformer = $serviceId;
    }

    /**
     * Creates and loads an object persister for a type.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadObjectPersister(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null !== $typeConfig->persister) {
            return;
        }

        if (! $typeConfig->model) {
            $typeConfig->persister = null;

            return;
        }

        $arguments = [
            $typeConfig->getReference(),
            $typeConfig->model,
        ];

        $serviceId = sprintf('fazland_elastica.object_persister.%s.%s', $typeConfig->index->name, $typeConfig->name);
        $serviceDef = new DefinitionDecorator('fazland_elastica.object_persister');
        foreach ($arguments as $i => $argument) {
            $serviceDef->replaceArgument($i, $argument);
        }

        $container->setDefinition($serviceId, $serviceDef);
        $typeConfig->persister = $serviceId;
    }

    /**
     * Loads a provider for a type.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadTypeProvider(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null === $typeConfig->persister || (null !== $typeConfig->provider && true !== $typeConfig->provider)) {
            if (true === $typeConfig->provider) {
                $typeConfig->provider = null;
            }

            return;
        }

        /*
         * Note: provider services may conflict with "prototype.driver", if the
         * index and type names were "prototype" and a driver, respectively.
         */
        $providerId = sprintf('fazland_elastica.provider.%s.%s', $typeConfig->index->name, $typeConfig->name);

        $providerDef = new DefinitionDecorator('fazland_elastica.provider.prototype.'.$typeConfig->persistenceDriver);
        $providerDef->replaceArgument(0, $typeConfig->index->name);
        $providerDef->replaceArgument(1, $typeConfig->name);
        $providerDef->replaceArgument(2, $typeConfig->model);
        // Propel provider can simply ignore Doctrine-specific options
        $providerDef->replaceArgument(4, $typeConfig->providerOptions);

        $container->setDefinition($providerId, $providerDef);
        $typeConfig->provider = $providerId;
    }

    /**
     * Loads doctrine listeners to handle indexing of new or updated objects.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadTypeListener(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null === $typeConfig->persister || (null !== $typeConfig->listener && true !== $typeConfig->listener)) {
            if (true === $typeConfig->provider) {
                $typeConfig->provider = null;
            }

            return;
        }

        /*
         * Note: listener services may conflict with "prototype.driver", if the
         * index and type names were "prototype" and a driver, respectively.
         */
        $abstractListenerId = sprintf('fazland_elastica.listener.prototype.%s', $typeConfig->persistenceDriver);
        $listenerId = sprintf('fazland_elastica.listener.%s.%s', $typeConfig->index->name, $typeConfig->name);
        $listenerDef = new DefinitionDecorator($abstractListenerId);
        $listenerDef->replaceArgument(0, new Reference($typeConfig->persister));
        $listenerDef->replaceArgument(2, [
            'identifier' => $typeConfig->modelIdentifier,
            'indexName' => $typeConfig->index->name,
            'typeName' => $typeConfig->name,
        ]);

        $tagName = null;
        switch ($typeConfig->persistenceDriver) {
            case 'orm':
                $tagName = 'doctrine.event_listener';
                break;

            case 'phpcr':
                $tagName = 'doctrine_phpcr.event_listener';
                break;

            case 'mongodb':
                $tagName = 'doctrine_mongodb.odm.event_listener';
                break;
        }

        if (null !== $tagName) {
            foreach ($this->getDoctrineEvents($typeConfig) as $event) {
                $listenerDef->addTag($tagName, ['event' => $event]);
            }
        }

        $listenerDef->addTag('doctrine.event_subscriber', ['priority' => 50]);

        $container->setDefinition($listenerId, $listenerDef);
        $typeConfig->listener = $listenerId;
    }

    /**
     * Map Elastica to Doctrine events for the current driver.
     *
     * @param TypeConfig $typeConfig
     *
     * @return \Generator
     */
    private function getDoctrineEvents(TypeConfig $typeConfig): \Generator
    {
        switch ($typeConfig->persistenceDriver) {
            case 'orm':
                $eventsClass = ORMEvents::class;
                break;

            case 'phpcr':
                $eventsClass = PHPCREvents::class;
                break;

            case 'mongodb':
                $eventsClass = MongoDBEvents::class;
                break;

            default:
                throw new InvalidArgumentException(sprintf('Cannot determine events for driver "%s"', $typeConfig->persistenceDriver));
        }

        $eventMapping = [
            'insert' => constant($eventsClass.'::postPersist'),
            'update' => constant($eventsClass.'::postUpdate'),
            'delete' => constant($eventsClass.'::preRemove'),
        ];

        foreach ($eventMapping as $event => $doctrineEvent) {
            if (isset($typeConfig->listenerOptions[$event]) && $typeConfig->listenerOptions[$event]) {
                yield $doctrineEvent;
            }
        }
    }

    /**
     * Loads a Type specific Finder.
     *
     * @param TypeConfig       $typeConfig
     * @param ContainerBuilder $container
     */
    private function loadTypeFinder(TypeConfig $typeConfig, ContainerBuilder $container)
    {
        if (null === $typeConfig->finder && null === $typeConfig->elasticaToModelTransformer) {
            return;
        }

        $indexName = $typeConfig->index->name;
        $typeName = $typeConfig->name;

        if (null === $typeConfig->finder) {
            $typeConfig->finder = sprintf('fazland_elastica.finder.%s.%s', $indexName, $typeName);
            $finderDef = new DefinitionDecorator('fazland_elastica.finder');
            $finderDef->replaceArgument(0, $typeConfig->getReference());
            $finderDef->replaceArgument(1, new Reference($typeConfig->elasticaToModelTransformer));
            $container->setDefinition($typeConfig->finder, $finderDef);
        }

        $indexTypeName = "$indexName/$typeName";
        $arguments = [$indexTypeName, new Reference($typeConfig->finder)];
        if ($typeConfig->repository) {
            $arguments[] = $typeConfig->repository;
        }

        $container->getDefinition('fazland_elastica.repository_manager')
            ->addMethodCall('addType', $arguments);
    }

    /**
     * Loads the index manager.
     *
     * @param ContainerBuilder $container
     **/
    private function loadIndexManager(ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition('fazland_elastica.index_manager');

        foreach ($this->indexConfigs as $indexConfig) {
            $managerDef->addMethodCall('addIndex', [$indexConfig->name, $indexConfig->getReference()]);
        }
    }

    /**
     * Makes sure a specific driver has been loaded.
     *
     * @param ContainerBuilder $container
     * @param string           $driver
     */
    private function loadDriver(ContainerBuilder $container, string $driver)
    {
        if (isset($this->loadedDrivers[$driver])) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load($driver.'.xml');
        $this->loadedDrivers[$driver] = true;
    }

    /**
     * Loads and configures the serializer prototype.
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadSerializer($config, ContainerBuilder $container)
    {
        $container->setAlias('fazland_elastica.serializer', $config['serializer']);

        $serializer = $container->getDefinition('fazland_elastica.serializer_callback_prototype');
        $serializer->setClass($config['callback_class']);

        if (is_subclass_of($config['callback_class'], ContainerAwareInterface::class)) {
            $serializer->addMethodCall('setContainer', [new Reference('service_container')]);
        }

        if (isset($config['groups'])) {
            $serializer->addMethodCall('setGroups', [$config['groups']]);
        }

        if (isset($config['serialize_null'])) {
            $serializer->addMethodCall('setSerializeNull', [$config['serialize_null']]);
        }

        if (isset($config['version'])) {
            $serializer->addMethodCall('setVersion', [$config['version']]);
        }
    }

    /**
     * Returns a reference to a client given its configured name.
     *
     * @param string $clientName
     *
     * @return Reference
     *
     * @throws \InvalidArgumentException
     */
    private function getClient(string $clientName = null): Reference
    {
        if (null === $clientName) {
            return new Reference('fazland_elastica.client');
        }

        if (! array_key_exists($clientName, $this->clients)) {
            throw new InvalidArgumentException(sprintf('The elastica client with name "%s" is not defined', $clientName));
        }

        return $this->clients[$clientName]['reference'];
    }

    private function addIndexToClient(IndexConfig $indexConfig, ContainerBuilder $container)
    {
        $clientName = $indexConfig->client;
        $clientDef = $container->findDefinition(null === $clientName ? 'fazland_elastica.client' : $this->clients[$clientName]['id']);

        $clientDef->addMethodCall('registerIndex', [$indexConfig->name, $indexConfig->service]);
    }

    private function loadCaches(array $cache, ContainerBuilder $container)
    {
        if ($cache['indexable_expression']) {
            $expressionLanguageDef = new Definition(ExpressionLanguage::class);
            $expressionLanguageDef->addMethodCall('registerProvider', [new Definition(ExpressionLanguageProvider::class)]);
            $expressionLanguageDef->addArgument(new Reference($cache['indexable_expression']));

            $container->getDefinition('fazland_elastica.indexable.default')
                ->addMethodCall('setExpressionLanguage', $expressionLanguageDef);
        }
    }

    private function addIndexAliasStrategy(IndexConfig $indexConfig, ContainerBuilder $container)
    {
        if (null === $indexConfig->alias || false === $indexConfig->alias) {
            return;
        }

        $indexDef = $container->findDefinition($indexConfig->service);
        $serviceId = sprintf('fazland_elastica.alias_strategy.index.%s', $indexConfig->indexName);

        switch ($indexConfig->alias) {
            case 'simple':
                $definition = new DefinitionDecorator('fazland_elastica.simple_alias_strategy_prototype');
                $container->setDefinition($serviceId, $definition);

                $indexDef->addMethodCall('setAliasStrategy', [new Reference($serviceId)]);
                break;

            default:
                $indexDef->addMethodCall('setAliasStrategy', [new Reference($indexConfig->alias)]);
                break;
        }
    }

    private function resolveMappingReferences()
    {
        foreach ($this->indexConfigs as $indexConfig) {
            foreach ($indexConfig->types as $typeConfig) {
                $mapping = $typeConfig->mapping;

                if (isset($mapping['properties']) && ! is_array($mapping['properties'])) {
                    $typeConfig->updateMappingProperties($this->getMappingReference($mapping['properties']));
                }
            }
        }
    }

    private function getMappingReference($reference)
    {
        if (! is_string($reference) || $reference[0] !== '@') {
            return $reference;
        }

        $ref = explode('/', substr($reference, 1), 2);

        if (! isset($this->indexConfigs[$ref[0]])) {
            throw new InvalidConfigurationException(sprintf('Cannot find referenced mapping %s', $reference));
        }

        $referenced = $this->indexConfigs[$ref[0]];
        if (! isset($referenced->types[$ref[1]])) {
            throw new InvalidConfigurationException(sprintf('Cannot find referenced mapping %s', $reference));
        }

        return $this->getMappingReference($referenced->types[$ref[1]]->mapping['properties']);
    }
}
