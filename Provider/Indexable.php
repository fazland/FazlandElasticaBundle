<?php

/**
 * This file is part of the FazlandElasticaBundle project.
 *
 * (c) Fazland <https://github.com/Fazland/FazlandElasticaBundle/graphs/contributors>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fazland\ElasticaBundle\Provider;

use Fazland\ElasticaBundle\Exception\InvalidCallbackException;
use Symfony\Component\DependencyInjection as DI;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class Indexable implements IndexableInterface, DI\ContainerAwareInterface
{
    use DI\ContainerAwareTrait;

    /**
     * An array of raw configured callbacks for all types.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * An array of initialised callbacks.
     *
     * @var array
     */
    protected $initialisedCallbacks = [];

    /**
     * An instance of ExpressionLanguage.
     *
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * Return whether the object is indexable with respect to the callback.
     *
     * @param string $indexName
     * @param string $typeName
     * @param mixed $object
     *
     * @return bool
     */
    public function isObjectIndexable(string $indexName, string $typeName, $object): bool
    {
        $type = sprintf('%s/%s', $indexName, $typeName);

        if (! array_key_exists($type, $this->callbacks)) {
            return true;
        }

        $callback = $this->getCallback($type, $object);

        try {
            return (bool) (is_string($callback)
                ? call_user_func([$object, $callback])
                : call_user_func($callback, $object));
        } catch (SyntaxError $e) {
            throw new InvalidCallbackException(sprintf('Callback for type "%s" is an invalid expression', $type), $e->getCode(), $e);
        }
    }

    /**
     * Add a callback to the indexable manager.
     *
     * @param string $type
     * @param $callback
     */
    public function addCallback(string $type, $callback)
    {
        $this->callbacks[$type] = $callback;
        unset($this->initialisedCallbacks[$type]);
    }

    /**
     * Retreives a cached callback, or creates a new callback if one is not found.
     *
     * @param string $type
     * @param object $object
     *
     * @return mixed
     */
    protected function getCallback($type, $object)
    {
        if (! array_key_exists($type, $this->initialisedCallbacks)) {
            $this->initialisedCallbacks[$type] = $this->buildCallback($type, $object);
        }

        return $this->initialisedCallbacks[$type];
    }

    /**
     * Builds and initialises a callback.
     *
     * @param string $type
     * @param object $object
     *
     * @return mixed
     */
    protected function buildCallback($type, $object)
    {
        $callback = $this->callbacks[$type];

        if (is_callable($callback) || is_callable([$object, $callback])) {
            return $callback;
        }

        if (is_string($callback)) {
            return function () use ($callback, $object) {
                return (bool) $this->getExpressionLanguage()->evaluate($callback, [
                    'object' => $object,
                    'container' => $this->container,
                ]);
            };
        }

        throw new InvalidCallbackException(sprintf('Callback for type "%s" is not a valid callback.', $type));
    }

    /**
     * Inject an ExpressionLanguage object.
     *
     * @param ExpressionLanguage $expressionLanguage
     */
    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * Returns the ExpressionLanguage class if it is available.
     *
     * @return ExpressionLanguage|null
     */
    protected function getExpressionLanguage()
    {
        if (null === $this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
            $this->expressionLanguage->registerProvider(new DI\ExpressionLanguageProvider());
        }

        return $this->expressionLanguage;
    }
}
