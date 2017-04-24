<?php

namespace Fazland\ElasticaBundle\Provider;

use Fazland\ElasticaBundle\Persister\ObjectPersisterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AbstractProvider.
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var IndexableInterface
     */
    protected $indexable;

    /**
     * @var string
     */
    protected $index;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    public function __construct(string $index, string $type)
    {
        $this->index = $index;
        $this->type = $type;
        $this->resolver = new OptionsResolver();
        $this->configureOptions();
    }

    protected function configureOptions()
    {
        $this->resolver->setDefaults([
            'skip_indexable_check' => false,
        ]);
    }

    /**
     * @param IndexableInterface $indexable
     */
    public function setIndexable(IndexableInterface $indexable)
    {
        $this->indexable = $indexable;
    }

    protected function isIndexable($object)
    {
        return $this->indexable->isObjectIndexable($this->index, $this->type, $object);
    }
}
