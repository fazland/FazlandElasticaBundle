<?php

namespace Fazland\ElasticaBundle\Tests\Subscriber;

use Elastica\Query;
use Fazland\ElasticaBundle\Paginator\PartialResultsInterface;
use Fazland\ElasticaBundle\Paginator\RawPaginatorAdapter;
use Fazland\ElasticaBundle\Subscriber\PaginateElasticaQuerySubscriber;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginateElasticaQuerySubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected function getAdapterMock()
    {
        return $this->getMockBuilder(RawPaginatorAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getResultSetMock()
    {
        return $this->getMockBuilder(PartialResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testShouldDoNothingIfSortParamIsEmpty()
    {
        $stack = new RequestStack();
        $stack->push(new Request());

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $adapter = $this->getAdapterMock();
        $adapter->expects($this->never())
            ->method('getQuery');
        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;

        $subscriber->items($event);
    }

    public function sortCases()
    {
        $tests = [];

        $expected = [
            'createdAt' => [
                'order' => 'asc',
                'ignore_unmapped' => true
            ]
        ];
        $tests[] = [$expected, new Request()];

        $expected = [
            'name' => [
                'order' => 'desc',
                'ignore_unmapped' => true
            ]
        ];
        $tests[] = [$expected, new Request(['ord' => 'name', 'az' => 'desc'])];

        $expected = [
            'updatedAt' => [
                'order' => 'asc',
                'ignore_unmapped' => true
            ]
        ];
        $tests[] = [$expected, new Request(['ord' => 'updatedAt', 'az' => 'invalid'])];

        return $tests;
    }

    /**
     * @dataProvider sortCases
     */
    public function testShouldSort(array $expected, Request $request)
    {
        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
        ];

        $subscriber->items($event);

        $this->assertEquals($expected, $query->getParam('sort'));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testShouldThrowIfFieldIsNotWhitelisted()
    {
        $stack = new RequestStack();
        $stack->push(new Request(['ord' => 'owner']));

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
            'sortFieldWhitelist' => ['createdAt', 'updatedAt'],
        ];

        $subscriber->items($event);
    }

    public function testShouldAddNestedPath()
    {
        $stack = new RequestStack();
        $stack->push(new Request(['ord' => 'owner.name']));

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
            'sortNestedPath' => 'owner',
        ];

        $subscriber->items($event);
        $this->assertEquals([
            'owner.name' => [
                'order' => 'asc',
                'ignore_unmapped' => true,
                'nested_path' => 'owner',
            ]
        ], $query->getParam('sort'));
    }

    public function testShouldInvokeCallableNestedPath()
    {
        $stack = new RequestStack();
        $stack->push(new Request(['ord' => 'owner.name']));

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
            'sortNestedPath' => function ($sortField) {
                $this->assertEquals('owner.name', $sortField);
                return 'owner';
            },
        ];

        $subscriber->items($event);
        $this->assertEquals([
            'owner.name' => [
                'order' => 'asc',
                'ignore_unmapped' => true,
                'nested_path' => 'owner',
            ]
        ], $query->getParam('sort'));
    }

    public function testShouldAddNestedFilter()
    {
        $stack = new RequestStack();
        $stack->push(new Request(['ord' => 'owner.name']));

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
            'sortNestedPath' => 'owner',
            'sortNestedFilter' => new Query\Term(['enabled' => ['value' => true]]),
        ];

        $subscriber->items($event);
        $this->assertEquals([
            'sort' => [
                'owner.name' => [
                    'order' => 'asc',
                    'ignore_unmapped' => true,
                    'nested_path' => 'owner',
                    'nested_filter' => [
                        'term' => [
                            'enabled' => ['value' => true]
                        ]
                    ]
                ]
            ],
            'query' => [
                'match_all' => new \stdClass()
            ]
        ], $query->toArray());
    }

    public function testShouldInvokeNestedFilterCallable()
    {
        $stack = new RequestStack();
        $stack->push(new Request(['ord' => 'owner.name']));

        $subscriber = new PaginateElasticaQuerySubscriber($stack);

        $query = new Query();
        $adapter = $this->getAdapterMock();
        $adapter->method('getQuery')
            ->willReturn($query);

        $adapter->method('getResults')
            ->willReturn($this->getResultSetMock());

        $event = new ItemsEvent(0, 10);
        $event->target = $adapter;
        $event->options = [
            'defaultSortFieldName' => 'createdAt',
            'sortFieldParameterName' => 'ord',
            'sortDirectionParameterName' => 'az',
            'sortNestedPath' => 'owner',
            'sortNestedFilter' => function ($sortField) {
                $this->assertEquals('owner.name', $sortField);
                return new Query\Term(['enabled' => ['value' => true]]);
            },
        ];

        $subscriber->items($event);
        $this->assertEquals([
            'sort' => [
                'owner.name' => [
                    'order' => 'asc',
                    'ignore_unmapped' => true,
                    'nested_path' => 'owner',
                    'nested_filter' => [
                        'term' => [
                            'enabled' => ['value' => true]
                        ]
                    ]
                ]
            ],
            'query' => [
                'match_all' => new \stdClass()
            ]
        ], $query->toArray());
    }
}
