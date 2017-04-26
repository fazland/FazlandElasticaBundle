<?php

namespace Fazland\ElasticaBundle\Tests\Manager;

use Fazland\ElasticaBundle\Manager\RepositoryManager;

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
    public function testThatGetRepositoryReturnsDefaultRepository()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $typeName = 'index/type';

        $manager = new RepositoryManager();
        $manager->addType($typeName, $finderMock);
        $repository = $manager->getRepository($typeName);
        $this->assertInstanceOf('Fazland\ElasticaBundle\Repository', $repository);
    }

    public function testThatGetRepositoryReturnsCustomRepository()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $typeName = 'index/type';

        $manager = new RepositoryManager();
        $manager->addType($typeName, $finderMock, 'Fazland\ElasticaBundle\Tests\Manager\CustomRepository');
        $repository = $manager->getRepository($typeName);
        $this->assertInstanceOf('Fazland\ElasticaBundle\Tests\Manager\CustomRepository', $repository);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThatGetRepositoryThrowsExceptionIfEntityNotConfigured()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $typeName = 'index/type';

        $manager = new RepositoryManager();
        $manager->addType($typeName, $finderMock);
        $manager->getRepository('Missing type');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThatGetRepositoryThrowsExceptionIfCustomRepositoryNotFound()
    {
        /** @var $finderMock \PHPUnit_Framework_MockObject_MockObject|\Fazland\ElasticaBundle\Finder\TransformedFinder */
        $finderMock = $this->getMockBuilder('Fazland\ElasticaBundle\Finder\TransformedFinder')
            ->disableOriginalConstructor()
            ->getMock();

        $typeName = 'index/type';

        $manager = new RepositoryManager();
        $manager->addType($typeName, $finderMock, 'Fazland\ElasticaBundle\Tests\MissingRepository');
        $manager->getRepository($typeName);
    }
}
