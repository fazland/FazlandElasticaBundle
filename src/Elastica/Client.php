<?php

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Elasticsearch\Endpoints\Cat\Aliases;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\RequestEvent;
use Fazland\ElasticaBundle\Event\ResponseEvent;
use Fazland\ElasticaBundle\Exception\UnknownIndexException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the default Elastica client to provide logging for errors that occur
 * during communication with ElasticSearch.
 *
 * @author Gordon Franke <info@nevalon.de>
 */
class Client extends Elastica\Client implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Index to service id map.
     *
     * @var string[]
     */
    private $indexServices = [];

    /**
     * Stores created indexes to avoid recreation.
     *
     * @var Index[]
     */
    private $indexes = [];

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param string $path
     * @param string $method
     * @param array  $data
     * @param array  $query
     *
     * @return \Elastica\Response
     */
    public function request($path, $method = Elastica\Request::GET, $data = [], array $query = [])
    {
        $event = new RequestEvent($path, $method, $data, $query);
        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(Events::REQUEST, $event);
        }

        $response = parent::request($event->getPath(), $event->getMethod(), $event->getData(), $event->getQuery());

        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(Events::RESPONSE, new ResponseEvent($this->_lastRequest, $this->_lastResponse));
        }

        return $response;
    }

    /**
     * @param string $name
     *
     * @return Elastica\Index
     */
    public function getIndex($name): Elastica\Index
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }

        if (! isset($this->indexServices[$name])) {
            throw new UnknownIndexException(sprintf('Unknown index "%s" requested.', $name));
        }

        return $this->indexes[$name] = $this->container->get($this->indexServices[$name]);
    }

    /**
     * Register a new index configuration in this client.
     *
     * @param string $name
     * @param string $serviceId
     */
    public function registerIndex(string $name, string $serviceId)
    {
        $this->indexServices[$name] = $serviceId;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
