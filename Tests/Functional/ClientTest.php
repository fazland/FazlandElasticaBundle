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
use Elastica\Connection\Strategy\RoundRobin;
use Elastica\Connection\Strategy\Simple;

/**
 * @group functional
 */
class ClientTest extends WebTestCase
{
    public function testConnectionStrategy()
    {
        $client = $this->createClient(['test_case' => 'Basic']);

        $es = $client->getContainer()->get('fazland_elastica.client.default');
        $this->assertInstanceOf(RoundRobin::class, $es->getConnectionStrategy());

        $es = $client->getContainer()->get('fazland_elastica.client.second_server');
        $this->assertInstanceOf(RoundRobin::class, $es->getConnectionStrategy());

        $es = $client->getContainer()->get('fazland_elastica.client.third');
        $this->assertInstanceOf(Simple::class, $es->getConnectionStrategy());
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
