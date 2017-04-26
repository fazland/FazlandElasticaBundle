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

use Elastica\Type\Mapping;
use Fazland\ElasticaBundle\Elastica\Type;
use Fazland\ElasticaBundle\Finder\TransformedFinder;
use Fazland\ElasticaBundle\Tests\Functional\app\complete\ETMTransformer;
use Fazland\ElasticaBundle\Tests\Functional\app\complete\Finder;
use Fazland\ElasticaBundle\Tests\Functional\app\complete\Provider;

/**
 * @group functional
 */
class ConfigurationTest extends WebTestCase
{
    public function testCompleteConfiguration()
    {
        $client = $this->createClient(['test_case' => 'complete']);
        $container = $client->getContainer();

        /** @var Type $type */
        $type = $container->get('fazland_elastica.index.index.type1');
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals('type1', $type->getName());

        $type = $container->get('fazland_elastica.index.index.type_with_finder');
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals('type_with_finder', $type->getName());

        $finder = $container->get('finder_for_type_with_finder');
        $this->assertInstanceOf(Finder::class, $finder);
        $this->assertEquals($type, $finder->type);

        /** @var TransformedFinder $finder */
        $finder = $container->get('fazland_elastica.finder.index.type_with_transformer');
        $this->assertInstanceOf(TransformedFinder::class, $finder);

        $reflClass = new \ReflectionClass($finder);
        $prop = $reflClass->getProperty('transformer');
        $prop->setAccessible(true);
        $transformer = $prop->getValue($finder);

        $this->assertInstanceOf(ETMTransformer::class, $transformer);
        $this->assertEquals($container->get('etm_transformer'), $transformer);

        $type = $container->get('fazland_elastica.index.index.type_with_provider');
        $this->assertInstanceOf(Provider::class, $type->getProvider());
        $this->assertEquals($container->get('provider1'), $type->getProvider());

        $type = $container->get('fazland_elastica.index.index.type_with_external_provider');
        $this->assertInstanceOf(Provider::class, $type->getProvider());
        $this->assertEquals($container->get('provider2'), $type->getProvider());
    }

    public function testPropertiesReference()
    {
        $client = $this->createClient(['test_case' => 'complete']);
        $container = $client->getContainer();

        $mapping = new Mapping();
        $mapping->setProperties([
            'field1' => ['type' => 'text'],
            'field2' => ['type' => 'text'],
        ]);

        /** @var Type $type_copy */
        $type_copy = $container->get('fazland_elastica.index.second_index.type1_copy');
        $this->assertEquals($mapping, $type_copy->buildMapping());
    }
}
