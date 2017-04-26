<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Tests\Provider;

use Fazland\ElasticaBundle\Provider\Indexable;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexableTest extends TestCase
{
    /**
     * @var ContainerInterface|ObjectProphecy
     */
    private $container;

    /**
     * @var Indexable
     */
    private $indexable;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get('indexableService')
            ->willReturn(new IndexableDecider());

        $this->indexable = new Indexable();
        $this->indexable->setContainer($this->container->reveal());
    }

    public function testIndexableUnknown()
    {
        $index = $this->indexable->isObjectIndexable('index', 'type', new Entity());

        $this->assertTrue($index);
    }

    /**
     * @dataProvider provideIsIndexableCallbacks
     */
    public function testValidIndexableCallbacks($callback, $return)
    {
        $this->indexable->addCallback('index/type', $callback);
        $index = $this->indexable->isObjectIndexable('index', 'type', new Entity());

        $this->assertEquals($return, $index);
    }

    /**
     * @dataProvider provideInvalidIsIndexableCallbacks
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidIsIndexableCallbacks($callback)
    {
        $this->indexable->addCallback('index/type', $callback);
        $this->indexable->isObjectIndexable('index', 'type', new Entity());
    }

    public function provideInvalidIsIndexableCallbacks()
    {
        return [
            ['nonexistentEntityMethod'],
            [['@indexableService', 'internalMethod']],
            [[new IndexableDecider(), 'internalMethod']],
            [42],
            ['entity.getIsIndexable() && nonexistentEntityFunction()'],
        ];
    }

    public function provideIsIndexableCallbacks()
    {
        return [
            ['isIndexable', false],
            [[new IndexableDecider(), 'isIndexable'], true],
            ['service("indexableService").isIndexable(object)', true],
            ['service("indexableService").__invoke(object)', true],
            [function (Entity $entity) {
                return $entity->maybeIndex();
            }, true],
            ['object.maybeIndex()', true],
            ['!object.isIndexable() && object.property == "abc"', true],
            ['object.property != "abc"', false],
            ['["array", "values"]', true],
            ['[]', false],
        ];
    }
}

class Entity
{
    public $property = 'abc';

    public function isIndexable()
    {
        return false;
    }

    public function maybeIndex()
    {
        return true;
    }
}

class IndexableDecider
{
    public function isIndexable(Entity $entity)
    {
        return ! $entity->isIndexable();
    }

    protected function internalMethod()
    {
    }

    public function __invoke($object)
    {
        return true;
    }
}
