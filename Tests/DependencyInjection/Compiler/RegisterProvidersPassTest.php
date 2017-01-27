<?php

namespace Fazland\ElasticaBundle\Tests\DependencyInjection\Compiler;

use Fazland\ElasticaBundle\DependencyInjection\Compiler\RegisterProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessShouldRegisterTaggedProviders()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $registryDefinition = new Definition();

        $container->setDefinition('fazland_elastica.provider_registry', $registryDefinition);
        $container->setAlias('fazland_elastica.index', 'fazland_elastica.index.foo');

        $container->setDefinition('provider.foo.a', $this->createProviderDefinition(['type' => 'a']));
        $container->setDefinition('provider.foo.b', $this->createProviderDefinition(['index' => 'foo', 'type' => 'b']));
        $container->setDefinition('provider.bar.a', $this->createProviderDefinition(['index' => 'bar', 'type' => 'a']));

        $pass->process($container);

        $calls = $registryDefinition->getMethodCalls();

        $this->assertEquals(['addProvider', ['foo', 'a', 'provider.foo.a']], $calls[0]);
        $this->assertEquals(['addProvider', ['foo', 'b', 'provider.foo.b']], $calls[1]);
        $this->assertEquals(['addProvider', ['bar', 'a', 'provider.bar.a']], $calls[2]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testProcessShouldRequireProviderImplementation()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $container->setDefinition('fazland_elastica.provider_registry', new Definition());
        $container->setAlias('fazland_elastica.index', 'fazland_elastica.index.foo');

        $providerDef = $this->createProviderDefinition();
        $providerDef->setClass('stdClass');

        $container->setDefinition('provider.foo.a', $providerDef);

        $pass->process($container);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testProcessShouldRequireTypeAttribute()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $container->setDefinition('fazland_elastica.provider_registry', new Definition());
        $container->setAlias('fazland_elastica.index', 'fazland_elastica.index.foo');

        $container->setDefinition('provider.foo.a', $this->createProviderDefinition());

        $pass->process($container);
    }

    private function createProviderDefinition(array $attributes = [])
    {
        $provider = $this->getMockBuilder('Fazland\ElasticaBundle\Provider\ProviderInterface')->getMock();

        $definition = new Definition(get_class($provider));
        $definition->addTag('fazland_elastica.provider', $attributes);

        return $definition;
    }
}
