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
}
