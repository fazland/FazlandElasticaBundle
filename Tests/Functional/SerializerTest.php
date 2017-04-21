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

/**
 * @group functional
 */
class SerializerTest extends WebTestCase
{
    public function testMappingIteratorToArrayField()
    {
        $client = $this->createClient(['test_case' => 'Serializer']);
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
     * Tests that the serialize_null configuration attribute works
     */
    public function testWithNullValues()
    {
        $client = $this->createClient(['test_case' => 'Serializer']);
        $container = $client->getContainer();

        $disabledNullPersister = $container->get('fazland_elastica.object_persister.index.type_serialize_null_disabled');
        $enabledNullPersister = $container->get('fazland_elastica.object_persister.index.type_serialize_null_enabled');

        $object = new TypeObj();
        $object->id = 1;
        $object->field1 = null;
        $disabledNullPersister->insertOne($object);
        $enabledNullPersister->insertOne($object);

        // Tests that attributes with null values are not persisted into an Elasticsearch type without the serialize_null option
        $disabledNullType = $container->get('fazland_elastica.index.index.type_serialize_null_disabled');
        $documentData = $disabledNullType->getDocument(1)->getData();
        $this->assertArrayNotHasKey('field1', $documentData);

        // Tests that attributes with null values are persisted into an Elasticsearch type with the serialize_null option
        $enabledNullType = $container->get('fazland_elastica.index.index.type_serialize_null_enabled');
        $documentData = $enabledNullType->getDocument(1)->getData();
        $this->assertArrayHasKey('field1', $documentData);
        $this->assertEquals($documentData['field1'], null);
    }

    /**
     * Tests that the serialize_null configuration attribute works
     */
    public function testWithDefaultConfiguration()
    {
        $client = $this->createClient(['test_case' => 'Serializer']);
        $container = $client->getContainer();

        $defaultConfigPersister = $container->get('fazland_elastica.object_persister.index.serializer_default_config');

        $object = new TypeObj();
        $object->id = 1;
        $object->field2 = 'FooBar';
        $object->field3 = null;
        $defaultConfigPersister->insertOne($object);

        // Tests that attributes with null values are not persisted into an Elasticsearch type without the serialize_null option
        $disabledNullType = $container->get('fazland_elastica.index.index.serializer_default_config');
        $documentData = $disabledNullType->getDocument(1)->getData();
        $this->assertArrayHasKey('field2', $documentData);
        $this->assertEquals($documentData['field2'], 'FooBar');
        $this->assertArrayHasKey('field3', $documentData);
        $this->assertEquals($documentData['field3'], null);
    }

    public function testUnmappedType()
    {
        $client = $this->createClient(['test_case' => 'Serializer']);
        $resetter = $client->getContainer()->get('fazland_elastica.resetter');
        $resetter->resetIndex($client->getContainer()->get('fazland_elastica.index.index'));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->deleteTmpDir('Serializer');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir('Serializer');
    }
}
