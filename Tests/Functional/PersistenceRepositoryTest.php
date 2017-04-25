<?php

declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Functional;

class PersistenceRepositoryTest extends WebTestCase
{
    public function testRepositoryShouldBeSetCorrectly()
    {
        $client = $this->createClient(['test_case' => 'ORM']);

        $repository = $client->getContainer()->get('fazland_elastica.manager')
            ->getRepository('index/type_with_repository');

        $this->assertNotNull($repository);
        $this->assertEquals(TypeObjectRepository::class, get_class($repository));
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
