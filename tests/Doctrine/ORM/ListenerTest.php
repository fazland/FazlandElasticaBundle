<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine\ORM;

use Fazland\ElasticaBundle\Tests\Doctrine\ListenerTest as BaseListenerTest;

class ListenerTest extends BaseListenerTest
{
    protected function getClassMetadataClass()
    {
        return 'Doctrine\ORM\Mapping\ClassMetadata';
    }

    protected function getLifecycleEventArgsClass()
    {
        return 'Doctrine\ORM\Event\LifecycleEventArgs';
    }

    protected function getListenerClass()
    {
        return 'Fazland\ElasticaBundle\Doctrine\Listener';
    }

    protected function getObjectManagerClass()
    {
        return 'Doctrine\ORM\EntityManager';
    }
}
