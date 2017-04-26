<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Tim Nagel <tim@nagel.com.au>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Fazland\ElasticaBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group functional
 */
class MappingToElasticaTest extends WebTestCase
{
    public function testResetIndexAddsMappings()
    {
        $client = $this->createClient(['test_case' => 'Basic']);
        $this->getIndex($client, 'index')->reset();

        $type = $this->getType($client);
        $mapping = $type->getMapping();

        $this->assertNotEmpty($mapping, 'Mapping was populated');

        $type = $this->getType($client, 'type');
        $mapping = $type->getMapping();
        $this->assertEquals('parent', $mapping['type']['_parent']['type']);

        $this->assertEquals('strict', $mapping['type']['dynamic']);
        $this->assertArrayHasKey('dynamic', $mapping['type']['properties']['dynamic_allowed']);
        $this->assertEquals('true', $mapping['type']['properties']['dynamic_allowed']['dynamic']);
    }

    public function testORMResetIndexAddsMappings()
    {
        $client = $this->createClient(['test_case' => 'ORM']);
        $this->getIndex($client, 'index')->reset();

        $type = $this->getType($client);
        $mapping = $type->getMapping();

        $this->assertNotEmpty($mapping, 'Mapping was populated');
    }

    public function testMappingIteratorToArrayField()
    {
        $client = $this->createClient(['test_case' => 'ORM']);
        $this->getIndex($client, 'index')->reset();

        $persister = $client->getContainer()->get('fazland_elastica.object_persister.index.type');

        $object = new TypeObj();
        $object->id = 1;
        $object->coll = new \ArrayIterator(['foo', 'bar']);
        $persister->insertOne($object);

        $object->coll = new \ArrayIterator(['foo', 'bar', 'bazz']);
        $object->coll->offsetUnset(1);

        $persister->replaceOne($object);
    }

    /**
     * @param Client $client
     * @param string $index
     *
     * @return \Elastica\Type
     */
    private function getIndex(Client $client, $index = 'index')
    {
        return $client->getContainer()->get('fazland_elastica.index.'.$index);
    }

    /**
     * @param Client $client
     * @param string $type
     *
     * @return \Elastica\Type
     */
    private function getType(Client $client, $type = 'type')
    {
        return $client->getContainer()->get('fazland_elastica.index.index.'.$type);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->deleteTmpDir('Basic');
        $this->deleteTmpDir('ORM');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir('Basic');
        $this->deleteTmpDir('ORM');
    }
}
