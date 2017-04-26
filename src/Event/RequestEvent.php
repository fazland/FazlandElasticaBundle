<?php

namespace Fazland\ElasticaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class RequestEvent extends Event
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $query;

    public function __construct(string $path, string $method, $data, array $query)
    {
        $this->path = $path;
        $this->method = $method;
        $this->data = $data;
        $this->query = $query;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): RequestEvent
    {
        $this->path = $path;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): RequestEvent
    {
        $this->method = $method;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): RequestEvent
    {
        $this->data = $data;

        return $this;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function setQuery(array $query): RequestEvent
    {
        $this->query = $query;

        return $this;
    }
}
