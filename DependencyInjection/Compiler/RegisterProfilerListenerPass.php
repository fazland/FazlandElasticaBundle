<?php

namespace Fazland\ElasticaBundle\DependencyInjection\Compiler;

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterProfilerListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (in_array(WebProfilerBundle::class, $container->getParameter('kernel.bundles'))) {
            $container->getDefinition('fazland_elastica.request_listener')
                ->addTag('kernel.event_subscriber');
        }
    }
}
