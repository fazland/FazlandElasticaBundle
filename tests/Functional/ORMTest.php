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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Elastica\Exception\NotFoundException;
use Fazland\ElasticaBundle\Elastica\Type;

/**
 * @group functional
 */
class ORMTest extends WebTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::deleteTmpDir('ORM');

        $client = self::createClient(['test_case' => 'ORM']);

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();

        $sm = $em->getConnection()->getSchemaManager();
        $sm->dropDatabase($container->getParameter('kernel.cache_dir').'/db.sqlite');
        $sm->createDatabase($container->getParameter('kernel.cache_dir').'/db.sqlite');

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    public function testORMIntegration()
    {
        $client = $this->createClient(['test_case' => 'ORM']);
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        $obj = new TypeObj();
        $obj->id = 4747;

        $em->persist($obj);
        $em->flush();

        /** @var Type $type */
        $type = $container->get('fazland_elastica.index.index.property_paths_type');
        $document = $type->getDocument(4747);

        $this->assertNotNull($document);

        $obj->field2 = 'foobar';
        $em->flush();

        $document = $type->getDocument(4747);
        $this->assertEquals('foobar', $document->getData()['field1']);

        $em->remove($obj);
        $em->flush();

        try {
            $type->getDocument(4747);
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $exception) {
            // OK
        }
    }

    public function testORMIntegrationWithMoreThanOneDocument()
    {
        $client = $this->createClient(['test_case' => 'ORM']);
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        $obj1 = new TypeObj();
        $obj1->id = 4748;

        $obj2 = new TypeObj();
        $obj2->id = 4749;

        $em->persist($obj1);
        $em->persist($obj2);
        $em->flush();

        /** @var Type $type */
        $type = $container->get('fazland_elastica.index.index.property_paths_type');
        $document = $type->getDocument(4748);

        $this->assertNotNull($document);

        $obj1->field2 = 'foobar1';
        $obj2->field2 = 'foobar2';
        $em->flush();

        $document = $type->getDocument(4749);
        $this->assertEquals('foobar2', $document->getData()['field1']);

        $em->remove($obj2);
        $em->remove($obj1);
        $em->flush();

        try {
            $type->getDocument(4748);
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $exception) {
            // OK
        }

        try {
            $type->getDocument(4749);
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $exception) {
            // OK
        }
    }
}
