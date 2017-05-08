<?php

namespace Fazland\ElasticaBundle\Exception;

class ObjectMissingException extends \RuntimeException implements ExceptionInterface
{
    public static function create(int $objectsCnt, array $ids)
    {
        $message = sprintf('Cannot find corresponding objects (%d) for all Elastica results (%d). IDs: %s', $objectsCnt, count($ids), join(', ', $ids));

        return new self($message);
    }
}
