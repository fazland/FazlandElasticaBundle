<?php

namespace Fazland\ElasticaBundle\DependencyInjection\Compiler;

use Fazland\ElasticaBundle\Provider\ProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterProvidersPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('fazland_elastica.provider') as $providerId => $tags) {
            $index = $type = null;
            $class = $container->getDefinition($providerId)->getClass();

            if (! $class || ! is_subclass_of($class, ProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Elastica provider "%s" with class "%s" must implement ProviderInterface.', $providerId, $class));
            }

            foreach ($tags as $tag) {
                if (! isset($tag['index'])) {
                    throw new \InvalidArgumentException(sprintf('Elastica provider "%s" must specify the "index" attribute.', $providerId));
                }

                if (! isset($tag['type'])) {
                    throw new \InvalidArgumentException(sprintf('Elastica provider "%s" must specify the "type" attribute.', $providerId));
                }

                $index = $tag['index'];
                $type = $tag['type'];
            }

            $container->findDefinition(sprintf('fazland_elastica.index.%s.%s', $index, $type))
                ->addMethodCall('setProvider', [new Reference($providerId)]);
        }
    }
}
