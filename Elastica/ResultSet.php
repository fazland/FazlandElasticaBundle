<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Elastica;

use Elastica;
use Fazland\ElasticaBundle\Exception\TransfomerNotSetException;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;

class ResultSet extends Elastica\ResultSet
{
    /**
     * @var ElasticaToModelTransformerInterface
     */
    private $transfomer;

    /**
     * @var object[]
     */
    private $transformed;

    /**
     * @param ElasticaToModelTransformerInterface $transfomer
     */
    public function setTransfomer(ElasticaToModelTransformerInterface $transfomer)
    {
        $this->transfomer = $transfomer;
    }

    /**
     * Gets the transformed results (ex: doctrine objects).
     *
     * @return array|\object[]
     */
    public function getTransformed()
    {
        if (null !== $this->transformed) {
            return $this->transformed;
        }

        if (null === $this->transfomer) {
            throw new TransfomerNotSetException('No transformer has been set for the result set.');
        }

        return $this->transformed = $this->transfomer->transform($this->getResults());
    }
}
