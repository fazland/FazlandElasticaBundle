<?php

namespace Fazland\ElasticaBundle\Tests\DependencyInjection;

use Fazland\ElasticaBundle\DependencyInjection\FazlandElasticaExtension;
use Fazland\ElasticaBundle\Tests\Functional\SubtypeType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class FazlandElasticaExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testExtensionSupportsDriverlessTypePersistence()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/fixtures/driverless_type.yml'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.index.test_index'));
        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.index.test_index.driverless'));
        $this->assertFalse($containerBuilder->hasDefinition('fazland_elastica.elastica_to_model_transformer.test_index.driverless'));
        $this->assertFalse($containerBuilder->hasDefinition('fazland_elastica.object_persister.test_index.driverless'));
    }

    public function testExtensionSupportsTypeOverriding()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/fixtures/override_type_class.yml'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.index.test_index'));
        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.index.test_index.driverless'));
        $this->assertEquals(SubtypeType::class, $containerBuilder->getDefinition('fazland_elastica.index.test_index.driverless')->getClass());
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testExtensionShouldThrowIfTypeIsNotAValidClass()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/fixtures/override_type_class_invalid.yml'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Related class "Fazland\ElasticaBundle\Tests\Functional\NonExistent" does not exists
     */
    public function testExtensionShouldThrowIfRelatedObjectIsNotAValidClass()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/fixtures/related_class_invalid.yml'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);
    }

    public function testExtensionShouldLoadSerializerForSingleTypeWhereEnabled()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/fixtures/serializer_per_type.yml'));

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.index.test_index.test.serializer.callback'));
        $this->assertFalse($containerBuilder->hasDefinition('fazland_elastica.index.test_index.foo.serializer.callback'));
    }

    public function testExtensionCorrectlyProcessesMagicAllIndexSettings()
    {
        $config = [
            'fazland_elastica' => [
                'clients' => [
                    'default' => [
                        'url' => 'http://localhost:9200',
                    ],
                ],
                'indexes' => [
                    '_all' => [
                        'settings' => [
                            'index.number_of_replicas' => 2,
                            'index.number_of_shards' => 4,
                        ],
                    ],
                    'test_index' => [
                        'settings' => [
                            'index.version' => 50149,
                        ],
                        'types' => [
                            'test' => []
                        ],
                    ],
                    'test_index_1' => [
                        'settings' => [
                            'index.number_of_shards' => 5,
                        ],
                        'types' => [
                            'test' => []
                        ],
                    ],
                    'test_index_2' => [
                        'types' => [
                            'test' => []
                        ],
                    ],
                ],
            ],
        ];

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);

        $definition = $containerBuilder->getDefinition('fazland_elastica.index.test_index');
        $settings = $definition->getArgument(1)->getArgument(2)['settings'];

        $this->assertInternalType('array', $settings);
        $this->assertEquals([
            'index.number_of_replicas' => 2,
            'index.number_of_shards' => 4,
            'index.version' => 50149,
        ], $settings);

        $definition = $containerBuilder->getDefinition('fazland_elastica.index.test_index_1');
        $settings = $definition->getArgument(1)->getArgument(2)['settings'];

        $this->assertInternalType('array', $settings);
        $this->assertEquals([
            'index.number_of_replicas' => 2,
            'index.number_of_shards' => 5,
        ], $settings);

        $definition = $containerBuilder->getDefinition('fazland_elastica.index.test_index_2');
        $settings = $definition->getArgument(1)->getArgument(2)['settings'];

        $this->assertInternalType('array', $settings);
        $this->assertEquals([
            'index.number_of_replicas' => 2,
            'index.number_of_shards' => 4,
        ], $settings);
    }

    public function testExtensionRegistersCorrectDoctrineSubscribers()
    {
        $config = [
            'fazland_elastica' => [
                'clients' => [
                    'default' => [
                        'url' => 'http://localhost:9200',
                    ],
                ],
                'indexes' => [
                    'test_index_orm' => [
                        'types' => [
                            'test' => [
                                'persistence' => [
                                    'driver' => 'orm',
                                    'model' => 'orm_model',
                                    'listener' => [
                                        'insert' => true,
                                        'update' => true,
                                        'delete' => true,
                                    ],
                                ]
                            ]
                        ],
                    ],
                    'test_index_mongodb' => [
                        'types' => [
                            'test' => [
                                'persistence' => [
                                    'driver' => 'mongodb',
                                    'model' => 'mongodb_model',
                                    'listener' => [
                                        'insert' => true,
                                        'update' => true,
                                        'delete' => true,
                                    ],
                                ]
                            ]
                        ],
                    ],
                    'test_index_phpcr' => [
                        'types' => [
                            'test' => [
                                'persistence' => [
                                    'driver' => 'phpcr',
                                    'model' => 'phpcr_model',
                                    'listener' => [
                                        'insert' => true,
                                        'update' => true,
                                        'delete' => true,
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.debug', true);

        $extension = new FazlandElasticaExtension();
        $extension->load($config, $containerBuilder);

        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.listener.test_index_orm.test'));
        $this->assertTrue($containerBuilder->getDefinition('fazland_elastica.listener.test_index_orm.test')->hasTag('doctrine.event_subscriber'));
        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.listener.test_index_mongodb.test'));
        $this->assertTrue($containerBuilder->getDefinition('fazland_elastica.listener.test_index_mongodb.test')->hasTag('doctrine_mongodb.event_subscriber'));
        $this->assertTrue($containerBuilder->hasDefinition('fazland_elastica.listener.test_index_phpcr.test'));
        $this->assertTrue($containerBuilder->getDefinition('fazland_elastica.listener.test_index_phpcr.test')->hasTag('doctrine_phpcr.event_subscriber'));
    }
}
