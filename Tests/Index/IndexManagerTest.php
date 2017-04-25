<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Index;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\IndexManager;
use PHPUnit\Framework\TestCase;

class IndexManagerTest extends TestCase
{
    private $indexes = [];

    /**
     * @var IndexManager
     */
    private $indexManager;

    public function setUp()
    {
        $this->indexManager = new IndexManager();

        foreach (['index1', 'index2', 'index3'] as $indexName) {
            $index = $this->prophesize(Index::class);
            $index->getName()->willReturn($indexName);

            $this->indexes[$indexName] = $index->reveal();
            $this->indexManager->addIndex($indexName, $index->reveal());
        }
    }

    public function testGetAllIndexes()
    {
        $this->assertEquals($this->indexes, $this->indexManager->getAllIndexes());
    }

    public function testGetIndex()
    {
        $this->assertSame($this->indexes['index1'], $this->indexManager->getIndex('index1'));
        $this->assertSame($this->indexes['index2'], $this->indexManager->getIndex('index2'));
        $this->assertSame($this->indexes['index3'], $this->indexManager->getIndex('index3'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetIndexShouldThrowExceptionForInvalidName()
    {
        $this->indexManager->getIndex('index4');
    }
}
