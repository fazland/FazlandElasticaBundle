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
}
