<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\DependencyInjection\Compiler;

use Fazland\ElasticaBundle\DependencyInjection\Compiler\RegisterProvidersPass;
use Fazland\ElasticaBundle\Provider\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterProvidersPassTest extends TestCase
{
    public function testProcessShouldRegisterTaggedProviders()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $registryDefinition = new Definition();

        $fooA = $container->register('fazland_elastica.index.foo.a', $registryDefinition);
        $fooB = $container->register('fazland_elastica.index.foo.b', $registryDefinition);
        $barA = $container->register('fazland_elastica.index.bar.a', $registryDefinition);

        $container->setDefinition('provider.foo.a', $this->createProviderDefinition(['index' => 'foo', 'type' => 'a']));
        $container->setDefinition('provider.foo.b', $this->createProviderDefinition(['index' => 'foo', 'type' => 'b']));
        $container->setDefinition('provider.bar.a', $this->createProviderDefinition(['index' => 'bar', 'type' => 'a']));

        $pass->process($container);

        $this->assertEquals(['setProvider', [new Reference('provider.foo.a')]], $fooA->getMethodCalls()[0]);
        $this->assertEquals(['setProvider', [new Reference('provider.foo.b')]], $fooB->getMethodCalls()[0]);
        $this->assertEquals(['setProvider', [new Reference('provider.bar.a')]], $barA->getMethodCalls()[0]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testProcessShouldRequireProviderImplementation()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $container->setDefinition('fazland_elastica.provider_registry', new Definition());

        $providerDef = $this->createProviderDefinition();
        $providerDef->setClass('stdClass');

        $container->setDefinition('provider.foo.a', $providerDef);

        $pass->process($container);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Elastica provider "provider.foo.a" must specify the "index" attribute.
     */
    public function testProcessShouldRequireIndexAttribute()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $container->setDefinition('fazland_elastica.provider_registry', new Definition());
        $container->setDefinition('provider.foo.a', $this->createProviderDefinition());

        $pass->process($container);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Elastica provider "provider.foo.a" must specify the "type" attribute.
     */
    public function testProcessShouldRequireTypeAttribute()
    {
        $container = new ContainerBuilder();
        $pass = new RegisterProvidersPass();

        $container->setDefinition('fazland_elastica.provider_registry', new Definition());
        $container->setDefinition('provider.foo.a', $this->createProviderDefinition(['index' => 'foo']));

        $pass->process($container);
    }

    private function createProviderDefinition(array $attributes = [])
    {
        $provider = $this->prophesize(ProviderInterface::class)->reveal();

        $definition = new Definition(get_class($provider));
        $definition->addTag('fazland_elastica.provider', $attributes);

        return $definition;
    }
}
