<?php

namespace Fazland\ElasticaBundle\Event;

use Elastica;
use Symfony\Component\EventDispatcher\Event;

class ResponseEvent extends Event
{
    /**
     * @var Elastica\Request
     */
    private $request;

    /**
     * @var Elastica\Response
     */
    private $response;

    public function __construct(Elastica\Request $request, Elastica\Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): Elastica\Request
    {
        return $this->request;
    }

    public function getResponse(): Elastica\Response
    {
        return $this->response;
    }
}
