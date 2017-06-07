<?php

namespace Fazland\ElasticaBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class RequestEvent extends Event
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

    /**
     * @var string
     */
    private $contentType;

    public function __construct(string $path, string $method, $data, array $query, string $contentType)
    {
        $this->path = $path;
        $this->method = $method;
        $this->data = $data;
        $this->query = $query;
        $this->contentType = $contentType;
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

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): RequestEvent
    {
        $this->contentType = $contentType;

        return $this;
    }
}
