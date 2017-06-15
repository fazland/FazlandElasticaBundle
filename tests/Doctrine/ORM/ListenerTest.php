<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Fazland\ElasticaBundle\Tests\Doctrine\ListenerTest as BaseListenerTest;

class ListenerTest extends BaseListenerTest
{
    protected function getClassMetadataClass()
    {
        return ClassMetadata::class;
    }

    protected function getLifecycleEventArgsClass()
    {
        return LifecycleEventArgs::class;
    }

    protected function getObjectManagerClass()
    {
        return EntityManager::class;
    }
}
