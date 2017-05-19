<?php

namespace Fazland\ElasticaBundle\Tests\Transformer;

use Elastica\Document;
use Elastica\Result;
use Fazland\ElasticaBundle\HybridResult;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerCollection;
use Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group legacy
 */
class ElasticaToModelTransformerCollectionTest extends TestCase
{
    /**
     * @var \Fazland\ElasticaBundle\Transformer\ElasticaToModelTransformerCollection
     */
    protected $collection;
    protected $transformers = [];

    protected function collectionSetup()
    {
        $transformer1 = $this->prophesize(ETMTransformer::class);
        $transformer1->getObjectClass()->willReturn(POPO::class);
        $transformer1->getIdentifierField()->willReturn('id');

        $transformer2 = $this->prophesize(ETMTransformer::class);
        $transformer2->getObjectClass()->willReturn(POPO2::class);
        $transformer2->getIdentifierField()->willReturn('id');

        $this->transformers = [
            'type1' => $transformer1,
            'type2' => $transformer2,
        ];

        $this->collection = new ElasticaToModelTransformerCollection([
            'type1' => $transformer1->reveal(),
            'type2' => $transformer2->reveal(),
        ]);
    }

    public function testGetObjectClass()
    {
        $this->collectionSetup();

        $objectClasses = $this->collection->getObjectClass();
        $this->assertEquals([
            'type1' => POPO::class,
            'type2' => POPO2::class,
        ], $objectClasses);
    }

    public function testTransformDelegatesToTransformers()
    {
        $this->collectionSetup();

        $document1 = new Document(123, ['data' => 'lots of data'], 'type1');
        $document2 = new Document(124, ['data' => 'not so much data'], 'type2');
        $result1 = new POPO(123, 'lots of data');
        $result2 = new POPO2(124, 'not so much data');

        $this->transformers['type1']->transform([$document1])
            ->shouldBeCalledTimes(1)
            ->willReturn([123 => $result1]);

        $this->transformers['type2']->transform([$document2])
            ->shouldBeCalledTimes(1)
            ->willreturn([124 => $result2]);

        $results = $this->collection->transform([$document1, $document2]);

        $this->assertEquals([
            $result1,
            $result2,
        ], $results);
    }

    public function testTransformOrder()
    {
        $this->collectionSetup();

        $document1 = new Document(123, ['data' => 'lots of data'], 'type1');
        $document2 = new Document(124, ['data' => 'not so much data'], 'type1');
        $result1 = new POPO(123, 'lots of data');
        $result2 = new POPO2(124, 'not so much data');

        $this->transformers['type1']->transform([$document1, $document2])
            ->shouldBeCalledTimes(1)
            ->willReturn([123 => $result1, 124 => $result2]);

        $results = $this->collection->transform([$document1, $document2]);

        $this->assertEquals([
            $result1,
            $result2,
        ], $results);
    }

    public function testTransformOrderWithIdAsObject()
    {
        $this->collectionSetup();

        $id1 = 'yo';
        $id2 = 'lo';
        $idObject1 = new IDObject($id1);
        $idObject2 = new IDObject($id2);
        $document1 = new Document($idObject1, ['data' => 'lots of data'], 'type1');
        $document2 = new Document($idObject2, ['data' => 'not so much data'], 'type1');
        $result1 = new POPO($idObject1, 'lots of data');
        $result2 = new POPO2($idObject2, 'not so much data');

        $this->transformers['type1']->transform([$document1, $document2])
            ->shouldBeCalledTimes(1)
            ->willReturn(['yo' => $result1, 'lo' => $result2]);

        $results = $this->collection->transform([$document1, $document2]);

        $this->assertEquals([
            $result1,
            $result2,
        ], $results);
    }

    public function testGetIdentifierFieldReturnsAMapOfIdentifiers()
    {
        $collection = new ElasticaToModelTransformerCollection([]);
        $identifiers = $collection->getIdentifierField();
        $this->assertInternalType('array', $identifiers);
        $this->assertEmpty($identifiers);

        $this->collectionSetup();
        $identifiers = $this->collection->getIdentifierField();
        $this->assertInternalType('array', $identifiers);
        $this->assertEquals(['type1' => 'id', 'type2' => 'id'], $identifiers);
    }

    public function elasticaResults()
    {
        $result = new Result(['_id' => 123, '_type' => 'type1']);
        $transformedObject = new POPO(123, []);

        yield [$result, $transformedObject];
    }

    /**
     * @dataProvider elasticaResults
     */
    public function testHybridTransformDecoratesResultsWithHybridResultObjects($result, $transformedObject)
    {
        $transformer = $this->prophesize(ElasticaToModelTransformerInterface::class);

        $transformer->transform(Argument::any())
            ->willReturn([$result->getId() => $transformedObject]);

        $collection = new ElasticaToModelTransformerCollection(['type1' => $transformer->reveal()]);

        $hybridResults = $collection->hybridTransform([$result]);

        $this->assertInternalType('array', $hybridResults);
        $this->assertNotEmpty($hybridResults);
        $this->assertContainsOnlyInstancesOf(HybridResult::class, $hybridResults);

        $hybridResult = array_pop($hybridResults);
        $this->assertEquals($result, $hybridResult->getResult());
        $this->assertEquals($transformedObject, $hybridResult->getTransformed());
    }
}

class ETMTransformer implements ElasticaToModelTransformerInterface
{
    public function transform($results)
    {
    }

    public function getObjectClass()
    {
    }

    public function getIdentifierField()
    {
    }
}

class POPO
{
    public $id;
    public $data;

    /**
     * @param mixed $id
     */
    public function __construct($id, $data)
    {
        $this->data = $data;
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}

class POPO2 extends POPO
{
}

class IDObject
{
    protected $id;

    /**
     * @param int|string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
