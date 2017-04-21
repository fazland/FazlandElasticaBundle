<?php

namespace Fazland\ElasticaBundle\Elastica;

use Elastica\Client as BaseClient;
use Elastica\Request;
use Fazland\ElasticaBundle\Configuration\IndexConfig;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\RequestEvent;
use Fazland\ElasticaBundle\Event\ResponseEvent;
use Fazland\ElasticaBundle\Exception\UnknownIndexException;
use Fazland\ElasticaBundle\Index\AliasStrategy\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the default Elastica client to provide logging for errors that occur
 * during communication with ElasticSearch.
 *
 * @author Gordon Franke <info@nevalon.de>
 */
class Client extends BaseClient
{
    /**
     * Indexes configurations for this client.
     *
     * @var IndexConfig[]
     */
    private $indexConfigs = [];

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
     * @var FactoryInterface
     */
    private $aliasStrategyFactory;

    /**
     * @param string $path
     * @param string $method
     * @param array  $data
     * @param array  $query
     *
     * @return \Elastica\Response
     */
    public function request($path, $method = Request::GET, $data = [], array $query = [])
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
     * @return Index|mixed
     */
    public function getIndex($name)
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }

        if (! isset($this->indexConfigs[$name])) {
            throw new UnknownIndexException(sprintf('Unknown index "%s" requested.', $name));
        }

        $config = $this->indexConfigs[$name];
        $index = new Index($this, $this->indexConfigs[$name]);
        $index->setEventDispatcher($this->eventDispatcher);

        if ($config->getAliasStrategy()) {
            $index->setAliasStrategy($this->aliasStrategyFactory->factory($config->getAliasStrategy(), $index));
        }

        return $this->indexes[$name] = $index;
    }

    /**
     * Register a new index configuration in this client.
     *
     * @param IndexConfig $indexConfig
     */
    public function registerIndex(IndexConfig $indexConfig)
    {
        $this->indexConfigs[$indexConfig->getName()] = $indexConfig;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Sets the alias strategy factory.
     *
     * @param FactoryInterface $factory
     */
    public function setAliasStrategyFactory(FactoryInterface $factory)
    {
        $this->aliasStrategyFactory = $factory;
    }
}
