<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine;

use Fazland\ElasticaBundle\Doctrine\RepositoryManager;
use Fazland\ElasticaBundle\Repository;

class CustomRepository
{
}

class Entity
{
}

/**
 * @author Richard Miller <info@limethinking.co.uk>
 */
class RepositoryManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testThatGetRepositoryCallsMainRepositoryManager()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var $registryMock \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ManagerRegistry */
        $registryMock = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $mainManager = $this->getMockBuilder('Fazland\ElasticaBundle\Manager\RepositoryManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $mainManager->method('getRepository')
            ->with($this->equalTo('index/type'))
            ->willReturn(new Repository($this->createMock(\Fazland\ElasticaBundle\Manager\RepositoryManager::class), $finderMock));

        $entityName = 'Fazland\ElasticaBundle\Tests\Manager\Entity';

        $manager = new RepositoryManager($registryMock, $mainManager);
        $manager->addEntity($entityName, 'index/type');
        $repository = $manager->getRepository($entityName);
        $this->assertInstanceOf('Fazland\ElasticaBundle\Repository', $repository);
    }

    public function testGetRepositoryShouldResolveEntityShortName()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var $registryMock \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ManagerRegistry */
        $registryMock = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registryMock->method('getAliasNamespace')
            ->with($this->equalTo('FazlandElasticaBundle'))
            ->willReturn('Fazland\ElasticaBundle\Tests\Manager');

        $mainManager = $this->getMockBuilder('Fazland\ElasticaBundle\Manager\RepositoryManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $mainManager->method('getRepository')
            ->with($this->equalTo('index/type'))
            ->willReturn(new Repository($this->createMock(\Fazland\ElasticaBundle\Manager\RepositoryManager::class), $finderMock));

        $entityName = 'Fazland\ElasticaBundle\Tests\Manager\Entity';

        $manager = new RepositoryManager($registryMock, $mainManager);
        $manager->addEntity($entityName, 'index/type');
        $repository = $manager->getRepository('FazlandElasticaBundle:Entity');
        $this->assertInstanceOf('Fazland\ElasticaBundle\Repository', $repository);
    }
}
