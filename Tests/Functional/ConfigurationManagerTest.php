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
class ConfigurationManagerTest extends WebTestCase
{
    public function testContainerSource()
    {
        $client = $this->createClient(array('test_case' => 'Basic'));
        $manager = $this->getManager($client);

        $index = $manager->getIndexConfiguration('index');

        $this->assertEquals('index', $index->getName());
        $this->assertGreaterThanOrEqual(2, count($index->getTypes()));
        $this->assertInstanceOf('Fazland\\ElasticaBundle\\Configuration\\TypeConfig', $index->getType('type'));
        $this->assertInstanceOf('Fazland\\ElasticaBundle\\Configuration\\TypeConfig', $index->getType('parent'));
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

    /**
     * @param Client $client
     *
     * @return \Fazland\ElasticaBundle\Configuration\ConfigManager
     */
    private function getManager(Client $client)
    {
        $manager = $client->getContainer()->get('fazland_elastica.config_manager');

        return $manager;
    }
}
