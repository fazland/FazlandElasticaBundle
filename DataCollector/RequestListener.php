<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\DataCollector;

use Elastica\Request;
use Elastica\Response;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\RequestEvent;
use Fazland\ElasticaBundle\Event\ResponseEvent;
use Fazland\ElasticaBundle\Logger\ElasticaLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class RequestListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public static function getSubscribedEvents()
    {
        return [
            Events::REQUEST => ['onRequest', -255],
            Events::RESPONSE => ['onResponse', -255],
        ];
    }

    public function setStopwatch(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    public function onRequest(RequestEvent $event)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start($this->getKey($event->getMethod(), $event->getPath()));
        }
    }

    public function onResponse(ResponseEvent $event)
    {
        $request = $event->getRequest();
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop($this->getKey($request->getMethod(), $request->getPath()));
        }

        $response = $event->getResponse();
        $this->logQuery($request, $response);
    }

    private function getKey(string $method, string $path)
    {
        return 'elastica '.$method.' '.$path;
    }

    /**
     * Log the query if we have an instance of ElasticaLogger.
     *
     * @param Request $request
     * @param Response $response
     */
    private function logQuery(Request $request, Response $response)
    {
        if (! $this->logger instanceof ElasticaLogger) {
            return;
        }

        $responseData = $response->getData();
        $connection = $request->getConnection();
        $connectionArray = [
            'host' => $connection->getHost(),
            'port' => $connection->getPort(),
            'transport' => $connection->getTransport(),
            'headers' => $connection->hasConfig('headers') ? $connection->getConfig('headers') : [],
        ];

        $this->logger->logQuery($request->getPath(), $request->getMethod(), $request->getData(),
            $response->getQueryTime(), $connectionArray, $request->getQuery(),
            $responseData['took'] ?? 0, $responseData['hits']['total'] ?? 0);
    }
}
