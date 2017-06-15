<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Resetter\DependencyInjection;

use Fazland\ElasticaBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * ConfigurationTest.
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    public function setUp()
    {
        $this->processor = new Processor();
    }

    public function testUnconfiguredConfiguration()
    {
        $configuration = $this->getConfigs([]);

        $this->assertSame([
            'clients' => [],
            'indexes' => [],
            'cache' => [
                'indexable_expression' => null,
            ],
        ], $configuration);
    }

    public function testClientConfiguration()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'url' => 'http://localhost:9200',
                    'retryOnConflict' => 5,
                ],
                'clustered' => [
                    'connections' => [
                        [
                            'url' => 'http://es1:9200',
                            'headers' => [
                                'Authorization' => 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==',
                                'Content-Type' => 'application/json',
                            ],
                        ],
                        [
                            'url' => 'http://es2:9200',
                            'headers' => [
                                'Authorization' => 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==',
                                'Content-Type' => 'application/json',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertCount(2, $configuration['clients']);
        $this->assertCount(1, $configuration['clients']['default']['connections']);
        $this->assertCount(0, $configuration['clients']['default']['connections'][0]['headers']);
        $this->assertEquals(5, $configuration['clients']['default']['connections'][0]['retryOnConflict']);

        $this->assertCount(2, $configuration['clients']['clustered']['connections']);
        $this->assertEquals('http://es2:9200/', $configuration['clients']['clustered']['connections'][1]['url']);
        $this->assertCount(2, $configuration['clients']['clustered']['connections'][1]['headers']);
        $this->assertArrayHasKey('Authorization', $configuration['clients']['clustered']['connections'][1]['headers']);
        $this->assertArrayHasKey('Content-Type', $configuration['clients']['clustered']['connections'][1]['headers']);
        $this->assertEquals('Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==', $configuration['clients']['clustered']['connections'][0]['headers']['Authorization']);
    }

    public function testLogging()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'logging_enabled' => [
                    'url' => 'http://localhost:9200',
                    'logger' => true,
                ],
                'logging_disabled' => [
                    'url' => 'http://localhost:9200',
                    'logger' => false,
                ],
                'logging_not_mentioned' => [
                    'url' => 'http://localhost:9200',
                ],
                'logging_custom' => [
                    'url' => 'http://localhost:9200',
                    'logger' => 'custom.service',
                ],
            ],
        ]);

        $this->assertCount(4, $configuration['clients']);

        $this->assertEquals('fazland_elastica.logger', $configuration['clients']['logging_enabled']['connections'][0]['logger']);
        $this->assertFalse($configuration['clients']['logging_disabled']['connections'][0]['logger']);
        $this->assertEquals('fazland_elastica.logger', $configuration['clients']['logging_not_mentioned']['connections'][0]['logger']);
        $this->assertEquals('custom.service', $configuration['clients']['logging_custom']['connections'][0]['logger']);
    }

    public function testSlashIsAddedAtTheEndOfServerUrl()
    {
        $config = [
            'clients' => [
                'default' => ['url' => 'http://www.github.com'],
            ],
        ];
        $configuration = $this->getConfigs($config);

        $this->assertEquals('http://www.github.com/', $configuration['clients']['default']['connections'][0]['url']);
    }

    public function testTypeConfig()
    {
        $this->getConfigs([
            'clients' => [
                'default' => ['url' => 'http://localhost:9200'],
            ],
            'indexes' => [
                'test' => [
                    'type_prototype' => [
                        'analyzer' => 'custom_analyzer',
                        'persistence' => [
                            'identifier' => 'ID',
                        ],
                        'serializer' => [
                            'groups' => ['Search'],
                            'version' => 1,
                            'serialize_null' => false,
                        ],
                    ],
                    'types' => [
                        'test' => [
                            'properties' => [
                                'title' => [],
                                'published' => ['type' => 'datetime'],
                                'body' => null,
                            ],
                            'persistence' => [
                                'listener' => [],
                            ],
                        ],
                        'test2' => [
                            'properties' => [
                                'title' => null,
                                'children' => [
                                    'type' => 'nested',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testClientConfigurationNoUrl()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'host' => 'localhost',
                    'port' => 9200,
                ],
            ],
        ]);

        $this->assertTrue(empty($configuration['clients']['default']['connections'][0]['url']));
    }

    public function testMappingsRenamedToProperties()
    {
        $configuration = $this->getConfigs([
                'clients' => [
                    'default' => ['url' => 'http://localhost:9200'],
                ],
                'indexes' => [
                    'test' => [
                        'types' => [
                            'test' => [
                                'properties' => [
                                    'title' => [],
                                    'published' => ['type' => 'datetime'],
                                    'body' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(3, $configuration['indexes']['test']['types']['test']['properties']);
    }

    public function testUnconfiguredType()
    {
        $configuration = $this->getConfigs([
                'clients' => [
                    'default' => ['url' => 'http://localhost:9200'],
                ],
                'indexes' => [
                    'test' => [
                        'types' => [
                            'test' => null,
                        ],
                    ],
                ],
            ]);

        $this->assertArrayHasKey('properties', $configuration['indexes']['test']['types']['test']);
    }

    public function testNestedProperties()
    {
        $this->getConfigs([
            'clients' => [
                'default' => ['url' => 'http://localhost:9200'],
            ],
            'indexes' => [
                'test' => [
                    'types' => [
                        'user' => [
                            'properties' => [
                                'field1' => [],
                            ],
                            'persistence' => [],
                        ],
                        'user_profile' => [
                            '_parent' => [
                                'type' => 'user',
                            ],
                            'properties' => [
                                'field1' => [],
                                'field2' => [
                                    'type' => 'nested',
                                    'properties' => [
                                        'nested_field1' => [
                                            'type' => 'integer',
                                        ],
                                        'nested_field2' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => [
                                                    'type' => 'integer',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testCompressionConfig()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'compression_enabled' => [
                    'compression' => true,
                ],
                'compression_disabled' => [
                    'compression' => false,
                ],
            ],
        ]);

        $this->assertTrue($configuration['clients']['compression_enabled']['connections'][0]['compression']);
        $this->assertFalse($configuration['clients']['compression_disabled']['connections'][0]['compression']);
    }

    public function testCompressionDefaultConfig()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [],
            ],
        ]);

        $this->assertFalse($configuration['clients']['default']['connections'][0]['compression']);
    }

    public function testTimeoutConfig()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'simple_timeout' => [
                    'url' => 'http://localhost:9200',
                    'timeout' => 123,
                ],
                'connect_timeout' => [
                    'url' => 'http://localhost:9200',
                    'connectTimeout' => 234,
                ],
            ],
        ]);

        $this->assertEquals(123, $configuration['clients']['simple_timeout']['connections'][0]['timeout']);
        $this->assertEquals(234, $configuration['clients']['connect_timeout']['connections'][0]['connectTimeout']);
    }

    public function testAWSConfig()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'aws_access_key_id' => 'AWS_KEY',
                    'aws_secret_access_key' => 'AWS_SECRET',
                    'aws_region' => 'AWS_REGION',
                    'aws_session_token' => 'AWS_SESSION_TOKEN',
                ],
            ],
        ]);

        $connection = $configuration['clients']['default']['connections'][0];
        $this->assertEquals('AWS_KEY', $connection['aws_access_key_id']);
        $this->assertEquals('AWS_SECRET', $connection['aws_secret_access_key']);
        $this->assertEquals('AWS_REGION', $connection['aws_region']);
        $this->assertEquals('AWS_SESSION_TOKEN', $connection['aws_session_token']);
    }

    public function testConnectionStrategy()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'connections' => [
                        [],
                    ],
                    'connectionStrategy' => 'Simple',
                ],
            ],
        ]);

        $this->assertEquals('Simple', $configuration['clients']['default']['connectionStrategy']);

        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'connections' => [
                        [],
                    ],
                    'connectionStrategy' => 'RoundRobin',
                ],
            ],
        ]);

        $this->assertEquals('RoundRobin', $configuration['clients']['default']['connectionStrategy']);

        $configuration = $this->getConfigs([
            'clients' => [
                'default' => [
                    'connections' => [
                        [],
                    ],
                    'connectionStrategy' => 'rand',
                ],
            ],
        ]);

        $this->assertEquals('rand', $configuration['clients']['default']['connectionStrategy']);

        try {
            $this->getConfigs([
                'clients' => [
                    'default' => [
                        'connections' => [
                            [],
                        ],
                        'connectionStrategy' => 'undefined_function',
                    ],
                ],
            ]);

            $this->fail('Expected '.InvalidConfigurationException::class.' to be thrown');
        } catch (InvalidConfigurationException $e) {
            $this->assertRegExp('/ConnectionStrategy must be "Simple", "RoundRobin" or a callable/', $e->getMessage());
        }
    }

    public function testEnableSerializerPerType()
    {
        $configuration = $this->getConfigs([
            'clients' => [
                'default' => ['url' => 'http://localhost:9200'],
            ],
            'serializer' => [],
            'indexes' => [
                'test' => [
                    'types' => [
                        'test' => [
                            'serializer' => [],
                            'properties' => [
                                'title' => [],
                                'published' => ['type' => 'datetime'],
                                'body' => null,
                            ],
                        ],
                        'foo' => [
                            'properties' => [
                                'title' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertArrayHasKey('serializer', $configuration['indexes']['test']['types']['test']);
        $this->assertArrayNotHasKey('serializer', $configuration['indexes']['test']['types']['foo']);
    }

    public function testListenerRelatedConfiguration()
    {
        $config = $this->getConfigs([
            'clients' => [
                'default' => ['url' => 'http://localhost:9200'],
            ],
            'indexes' => [
                'test' => [
                    'types' => [
                        'test' => [
                            'properties' => [
                                'title' => [],
                                'published' => ['type' => 'datetime'],
                                'body' => null,
                            ],
                            'persistence' => [
                                'listener' => [
                                    'related' => [
                                        'NS\\TestRelatedObj' => [
                                            'test',
                                            'test.anotherTest'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertArrayHasKey('related', $config['indexes']['test']['types']['test']['persistence']['listener']);
        $this->assertCount(1, $config['indexes']['test']['types']['test']['persistence']['listener']['related']);
        $this->assertCount(2, $config['indexes']['test']['types']['test']['persistence']['listener']['related']['NS\\TestRelatedObj']);
    }

    private function getConfigs(array $configArray)
    {
        $configuration = new Configuration(true);

        return $this->processor->processConfiguration($configuration, [$configArray]);
    }
}
