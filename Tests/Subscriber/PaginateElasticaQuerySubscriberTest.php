<?php

namespace Fazland\ElasticaBundle\Tests\Subscriber;

use Elastica\Aggregation\Terms;
use Elastica\Query;
use Fazland\ElasticaBundle\Paginator\RawPaginatorAdapter;
use Fazland\ElasticaBundle\Subscriber\PaginateElasticaQuerySubscriber;
use Knp\Component\Pager\Event\PaginationEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class PaginateElasticaQuerySubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldPaginate()
    {
        $subscriber = new PaginateElasticaQuerySubscriber();
        $subscriber->setRequest(new Request());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $searchable = $this->getMockBuilder('Elastica\SearchableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $self = $this;
        $resultSet = $this->getMockBuilder('Elastica\ResultSet')
            ->disableOriginalConstructor()
            ->getMock();

        $resultSet->expects($this->any())
            ->method('getTotalHits')
            ->willReturn(100);
        $resultSet->expects($this->any())
            ->method('getResults')
            ->willReturn([]);

        $searchable->expects($this->once())
            ->method('search')
            ->willReturnCallback(function (Query $query) use ($self, $resultSet) {
                $self->assertEquals(80, $query->getParam('from'));
                $self->assertEquals(20, $query->getParam('size'));

                return $resultSet;
            });

        $q = new Query(new Query\MatchAll());
        $q->addAggregation(new Terms('term_agg'));

        $adapter = new RawPaginatorAdapter($searchable, $q);

        $p->paginate($adapter, 5, 20);
    }
}


class MockPaginationSubscriber implements EventSubscriberInterface
{
    static function getSubscribedEvents()
    {
        return array(
            'knp_pager.pagination' => array('pagination', 0)
        );
    }

    function pagination(PaginationEvent $e)
    {
        $e->setPagination(new SlidingPagination());
        $e->stopPropagation();
    }
}
