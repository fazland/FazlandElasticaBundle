<?php

namespace Fazland\ElasticaBundle\Tests;

use Fazland\ElasticaBundle\Finder\TransformedFinder;
use Fazland\ElasticaBundle\Manager\RepositoryManager;
use Fazland\ElasticaBundle\Repository;

/**
 * @author Richard Miller <info@limethinking.co.uk>
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testThatFindCallsFindOnFinder()
    {
        $testQuery = 'Test Query';

        $finderMock = $this->getFinderMock($testQuery);
        $repository = new Repository($this->getRepositoryManagerMock(), $finderMock);
        $repository->find($testQuery);
    }

    public function testThatFindCallsFindOnFinderWithLimit()
    {
        $testQuery = 'Test Query';
        $testLimit = 20;

        $finderMock = $this->getFinderMock($testQuery, $testLimit);
        $repository = new Repository($this->getRepositoryManagerMock(), $finderMock);
        $repository->find($testQuery, $testLimit);
    }

    public function testThatFindPaginatedCallsFindPaginatedOnFinder()
    {
        $testQuery = 'Test Query';

        $finderMock = $this->getFinderMock($testQuery, [], 'findPaginated');
        $repository = new Repository($this->getRepositoryManagerMock(), $finderMock);
        $repository->findPaginated($testQuery);
    }

    public function testThatCreatePaginatorCreatesAPaginatorViaFinder()
    {
        $testQuery = 'Test Query';

        $finderMock = $this->getFinderMock($testQuery, [], 'createPaginatorAdapter');
        $repository = new Repository($this->getRepositoryManagerMock(), $finderMock);
        $repository->createPaginatorAdapter($testQuery);
    }

    public function testThatFindHybridCallsFindHybridOnFinder()
    {
        $testQuery = 'Test Query';

        $finderMock = $this->getFinderMock($testQuery, null, 'findHybrid');
        $repository = new Repository($this->getRepositoryManagerMock(), $finderMock);
        $repository->findHybrid($testQuery);
    }

    /**
     * @param string $testQuery
     * @param mixed  $testLimit
     * @param string $method
     *
     * @return \Fazland\ElasticaBundle\Finder\TransformedFinder
     */
    private function getFinderMock($testQuery, $testLimit = null, $method = 'find')
    {
        $finderMock = $this->getMockBuilder(TransformedFinder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $finderMock->expects($this->once())
            ->method($method)
            ->with($this->equalTo($testQuery), $this->equalTo($testLimit));

        return $finderMock;
    }

    private function getRepositoryManagerMock()
    {
        return $this->createMock(RepositoryManager::class);
    }
}
