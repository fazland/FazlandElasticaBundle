<?php

namespace Fazland\ElasticaBundle;

use Fazland\ElasticaBundle\DependencyInjection\Compiler\ConfigSourcePass;
use Fazland\ElasticaBundle\DependencyInjection\Compiler\IndexPass;
use Fazland\ElasticaBundle\DependencyInjection\Compiler\RegisterProvidersPass;
use Fazland\ElasticaBundle\DependencyInjection\Compiler\TransformerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class FazlandElasticaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigSourcePass());
        $container->addCompilerPass(new IndexPass());
        $container->addCompilerPass(new RegisterProvidersPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new TransformerPass());
    }
}
