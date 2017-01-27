<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IndexPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (! $container->hasDefinition('fazland_elastica.index_manager')) {
            return;
        }

        $indexes = [];
        foreach ($container->findTaggedServiceIds('fazland_elastica.index') as $id => $tags) {
            foreach ($tags as $tag) {
                $indexes[$tag['name']] = new Reference($id);
            }
        }

        $container->getDefinition('fazland_elastica.index_manager')->replaceArgument(0, $indexes);
    }
}
