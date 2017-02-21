<?php

namespace Fazland\ElasticaBundle\Subscriber;

use Fazland\ElasticaBundle\Paginator\PaginatorAdapterInterface;
use Fazland\ElasticaBundle\Paginator\PartialResultsInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginateElasticaQuerySubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param ItemsEvent $event
     */
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof PaginatorAdapterInterface) {
            // Add sort to query
            $this->setSorting($event);

            /** @var $results PartialResultsInterface */
            $results = $event->target->getResults($event->getOffset(), $event->getLimit());

            $event->count = $results->getTotalHits();
            $event->items = $results->toArray();
            $aggregations = $results->getAggregations();
            if (null != $aggregations) {
                $event->setCustomPaginationParameter('aggregations', $aggregations);
            }

            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }

    /**
     * Adds knp paging sort to query.
     *
     * @param ItemsEvent $event
     */
    protected function setSorting(ItemsEvent $event)
    {
        $options = $event->options;
        $request = $this->getRequest();
        $sortField = null !== $request ? $request->get($options['sortFieldParameterName']) : null;

        if (! $sortField && isset($options['defaultSortFieldName'])) {
            $sortField = $options['defaultSortFieldName'];
        }

        if (! empty($sortField)) {
            $event->target->getQuery()->setSort([
                $sortField => $this->getSort($sortField, $options),
            ]);
        }
    }

    protected function getSort($sortField, array $options = [])
    {
        $ignoreUnmapped = isset($options['sortIgnoreUnmapped']) ? $options['sortIgnoreUnmapped'] : true;
        $sort = [
            'order' => $this->getSortDirection($sortField, $options),
            'ignore_unmapped' => $ignoreUnmapped,
        ];

        if (isset($options['sortNestedPath'])) {
            $path = is_callable($options['sortNestedPath']) ?
                $options['sortNestedPath']($sortField) : $options['sortNestedPath'];

            if (! empty($path)) {
                $sort['nested_path'] = $path;
            }
        }

        if (isset($options['sortNestedFilter'])) {
            $filter = is_callable($options['sortNestedFilter']) ?
                $options['sortNestedFilter']($sortField) : $options['sortNestedFilter'];

            if (! empty($filter)) {
                $sort['nested_filter'] = $filter;
            }
        }

        return $sort;
    }

    protected function getSortDirection($sortField, array $options = [])
    {
        $dir = 'asc';
        $request = $this->getRequest();
        $sortDirection = null !== $request ? $request->get($options['sortDirectionParameterName']) : null;

        if (empty($sortDirection) && isset($options['defaultSortDirection'])) {
            $sortDirection = $options['defaultSortDirection'];
        }

        if ('desc' === strtolower($sortDirection)) {
            $dir = 'desc';
        }

        // check if the requested sort field is in the sort whitelist
        if (isset($options['sortFieldWhitelist']) && ! in_array($sortField, $options['sortFieldWhitelist'])) {
            throw new \UnexpectedValueException(sprintf('Cannot sort by: [%s] this field is not in whitelist', $sortField));
        }

        return $dir;
    }

    private function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
