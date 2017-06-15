<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine\PHPCR;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Fazland\ElasticaBundle\Tests\Doctrine\ListenerTest as BaseListenerTest;

class ListenerTest extends BaseListenerTest
{
    public function setUp()
    {
        if (! class_exists('Doctrine\ODM\PHPCR\DocumentManager')) {
            $this->markTestSkipped('Doctrine PHPCR is not available.');
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
