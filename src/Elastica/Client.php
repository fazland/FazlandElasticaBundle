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
     * @var array
     */
    private $aliases = [];

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

    /**
     * @inheritdoc
     */
    public function addDocuments(array $docs)
    {
        if (empty($docs)) {
            throw new Elastica\Exception\InvalidException('Array has to consist of at least one element');
        }

        $bulk = new Elastica\Bulk($this);
        $bulk->addDocuments(iterator_to_array($this->normalizeDocumentIndex(...$docs)));

        return $bulk->send();
    }

    /**
     * @inheritdoc
     */
    public function updateDocument($id, $data, $index, $type, array $options = [])
    {
        if (! isset($this->aliases[$index])) {
            return parent::updateDocument($id, $data, $index, $type, $options);
        }

        $response = null;
        foreach ($this->aliases[$index] as $idx) {
            $response = parent::updateDocument($id, $data, $idx, $type, $options);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function deleteDocuments(array $docs)
    {
        if (empty($docs)) {
            throw new Elastica\Exception\InvalidException('Array has to consist of at least one element');
        }

        $bulk = new Elastica\Bulk($this);
        $bulk->addDocuments(iterator_to_array($this->normalizeDocumentIndex(...$docs)), Elastica\Bulk\Action::OP_TYPE_DELETE);

        return $bulk->send();
    }

    /**
     * @inheritdoc
     */
    public function deleteIds(array $ids, $index, $type, $routing = false)
    {
        if (empty($ids)) {
            throw new Elastica\Exception\InvalidException('Array has to consist of at least one id');
        }

        if ($index instanceof Elastica\Index) {
            $index = $index->getName();
        }

        if ($type instanceof Elastica\Type) {
            $type = $type->getName();
        }

        if (! isset($this->aliases[$index])) {
            return parent::deleteIds($ids, $index, $type, $routing);
        }

        $bulk = new Elastica\Bulk($this);
        foreach ($this->aliases[$index] as $idx) {
            foreach ($ids as $id) {
                $action = new Elastica\Bulk\Action(Elastica\Bulk\Action::OP_TYPE_DELETE);
                $action->setId($id);
                $action->setIndex($idx);
                $action->setType($type);

                if (!empty($routing)) {
                    $action->setRouting($routing);
                }

                $bulk->addAction($action);
            }
        }

        return $bulk->send();
    }

    private function normalizeDocumentIndex(Elastica\Document ...$documents)
    {
        foreach ($documents as $document) {
            $index = $document->getIndex();
            if (! isset($this->aliases[$index])) {
                yield $document;
                continue;
            }

            foreach ($this->aliases[$index] as $index) {
                $cloned = clone $document;
                $cloned->setIndex($index);

                yield $cloned;
            }
        }
    }

    protected function _initConnections()
    {
        parent::_initConnections();

        try {
            $indexes = $this->request('/_alias')->getData();
        } catch (Elastica\Exception\ConnectionException $e) {
            return;
        }

        foreach ($indexes as $index => $aliases) {
            foreach (array_keys($aliases['aliases']) as $alias) {
                $this->aliases[$alias][] = $index;
            }
        }

        $this->aliases = array_filter($this->aliases, function ($indexes) {
            return count($indexes) > 1;
        });
    }
}
