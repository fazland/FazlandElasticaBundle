<?php

namespace Fazland\ElasticaBundle\Tests\Doctrine\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Elastica\Result;
use Fazland\ElasticaBundle\Doctrine\ORM\ElasticaToModelTransformer;
use Fazland\ElasticaBundle\Tests\Functional\TypeObj;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class TestRepository extends EntityRepository
{
    public function customQueryBuilderCreator(string $alias)
    {
    }
}

class ElasticaToModelTransformerTest extends TestCase
{
    /**
     * @var ManagerRegistry|ObjectProphecy
     */
    protected $registry;

    /**
     * @var EntityManager|ObjectProphecy
     */
    protected $manager;

    /**
     * @var TestRepository|ObjectProphecy
     */
    protected $repository;

    /**
     * @var string
     */
    protected $objectClass = 'stdClass';

    protected function setUp()
    {
        $this->registry = $this->prophesize(ManagerRegistry::class);
        $this->registry
            ->getManagerForClass($this->objectClass)
            ->willReturn($this->manager = $this->prophesize(EntityManager::class));

        $this->manager
            ->getRepository($this->objectClass)
            ->willReturn($this->repository = $this->prophesize(TestRepository::class));
    }

    /**
     * Tests that the Transformer uses the query_builder_method configuration option
     * allowing configuration of createQueryBuilder call.
     */
    public function testTransformUsesQueryBuilderMethodConfiguration()
    {
        $this->repository
            ->customQueryBuilderCreator(ElasticaToModelTransformer::ENTITY_ALIAS)
            ->shouldBeCalled()
            ->willReturn($qb = $this->prophesize(QueryBuilder::class));

        $qb->expr()
            ->willReturn(new ExpressionBuilder($this->prophesize(Connection::class)->reveal()));
        $qb->andWhere(Argument::type('string'))->willReturn($qb);
        $qb->setParameter(Argument::cetera())->willReturn($qb);
        $qb->getQuery()->willReturn($query = $this->prophesize(AbstractQuery::class));

        $query->setHydrationMode(Query::HYDRATE_OBJECT)->willReturn($query);
        $query->execute()->willReturn([
            $obj = new TypeObj(),
        ]);

        $obj->id = 1;

        $this->repository
            ->createQueryBuilder(Argument::any())
            ->shouldNotBeCalled();

        $transformer = new ElasticaToModelTransformer($this->registry->reveal(), $this->objectClass, [
            'query_builder_method' => 'customQueryBuilderCreator',
            'identifier' => 'id',
        ]);

        $doc = $this->prophesize(Result::class);
        $doc->getId()->willReturn(1);
        $doc->getHighlights()->willReturn([]);

        $transformer->transform([
            $doc->reveal()
        ]);
    }

    /**
     * Tests that the Transformer uses the query_builder_method configuration option
     * allowing configuration of createQueryBuilder call.
     */
    public function testTransformUsesDefaultQueryBuilderMethodConfiguration()
    {
        $this->repository
            ->createQueryBuilder(ElasticaToModelTransformer::ENTITY_ALIAS)
            ->shouldBeCalled()
            ->willReturn($qb = $this->prophesize(QueryBuilder::class));

        $qb->expr()
            ->willReturn(new ExpressionBuilder($this->prophesize(Connection::class)->reveal()));
        $qb->andWhere(Argument::type('string'))->willReturn($qb);
        $qb->setParameter(Argument::cetera())->willReturn($qb);
        $qb->getQuery()->willReturn($query = $this->prophesize(AbstractQuery::class));

        $query->setHydrationMode(Query::HYDRATE_OBJECT)->willReturn($query);
        $query->execute()->willReturn([
            $obj = new TypeObj(),
        ]);

        $obj->id = 1;

        $transformer = new ElasticaToModelTransformer($this->registry->reveal(), $this->objectClass, [
            'identifier' => 'id',
        ]);

        $doc = $this->prophesize(Result::class);
        $doc->getId()->willReturn(1);
        $doc->getHighlights()->willReturn([]);

        $transformer->transform([
            $doc->reveal()
        ]);
    }

    /**
     * Checks that the 'hints' parameter is used on the created query.
     */
    public function testUsesHintsConfigurationIfGiven()
    {
        $this->repository
            ->createQueryBuilder(ElasticaToModelTransformer::ENTITY_ALIAS)
            ->shouldBeCalled()
            ->willReturn($qb = $this->prophesize(QueryBuilder::class));

        $qb->expr()
            ->willReturn(new ExpressionBuilder($this->prophesize(Connection::class)->reveal()));
        $qb->andWhere(Argument::type('string'))->willReturn($qb);
        $qb->setParameter(Argument::cetera())->willReturn($qb);
        $qb->getQuery()->willReturn($query = $this->prophesize(AbstractQuery::class));

        $query->setHint('customHintName', 'Custom\Hint\Class')->willReturn($query);
        $query->setHydrationMode(Query::HYDRATE_OBJECT)->willReturn($query);
        $query->execute()->willReturn([
            $obj = new TypeObj(),
        ]);
        $obj->id = 1;

        $transformer = new ElasticaToModelTransformer($this->registry->reveal(), $this->objectClass, [
            'identifier' => 'id',
            'hints' => [
                ['name' => 'customHintName', 'value' => 'Custom\Hint\Class'],
            ],
        ]);

        $doc = $this->prophesize(Result::class);
        $doc->getId()->willReturn(1);
        $doc->getHighlights()->willReturn([]);

        $transformer->transform([
            $doc->reveal()
        ]);
    }
}
