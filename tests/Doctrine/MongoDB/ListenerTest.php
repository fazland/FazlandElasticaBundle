<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine\MongoDB;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Fazland\ElasticaBundle\Doctrine\Listener;
use Fazland\ElasticaBundle\Tests\Doctrine\ListenerTest as BaseListenerTest;

class ListenerTest extends BaseListenerTest
{
    public function setUp()
    {
        if (! class_exists('Doctrine\ODM\MongoDB\DocumentManager')) {
            $this->markTestSkipped('Doctrine MongoDB ODM is not available.');
        }
    }

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
        return DocumentManager::class;
    }
}
