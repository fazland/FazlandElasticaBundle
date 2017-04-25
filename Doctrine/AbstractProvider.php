<?php

namespace Fazland\ElasticaBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Fazland\ElasticaBundle\Provider\AbstractProvider as BaseAbstractProvider;
use Fazland\ElasticaBundle\Provider\CountAwareProviderInterface;

abstract class AbstractProvider extends BaseAbstractProvider implements CountAwareProviderInterface
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var array
     */
    protected $options;

    public function __construct(string $index, string $type, string $modelClass, ManagerRegistry $managerRegistry, array $options = [])
    {
        parent::__construct($index, $type);

        $this->modelClass = $modelClass;
        $this->managerRegistry = $managerRegistry;
        $this->options = $this->resolver->resolve($options);
    }

    /**
     * Creates the query builder, which will be used to fetch objects to index.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return object
     */
    abstract protected function createQueryBuilder($method, array $arguments = []);

    public function clear()
    {
        $this->managerRegistry
            ->getManagerForClass($this->modelClass)
            ->clear();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions()
    {
        parent::configureOptions();

        $this->resolver->setDefaults([
            'query_builder_method' => 'createQueryBuilder',
        ]);
    }
}
