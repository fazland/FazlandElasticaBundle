<?php

namespace Fazland\ElasticaBundle\Tests\Functional;

class PersistenceRepositoryTest extends WebTestCase
{
    public function testRepositoryShouldBeSetCorrectly()
    {
        $client = $this->createClient(['test_case' => 'ORM']);

        $repository = $client->getContainer()->get('fazland_elastica.manager.orm')
            ->getRepository('Fazland\ElasticaBundle\Tests\Functional\TypeObject');

        $this->assertNotNull($repository);
        $this->assertEquals('Fazland\ElasticaBundle\Tests\Functional\TypeObjectRepository', get_class($repository));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->deleteTmpDir('Basic');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir('Basic');
    }
}
