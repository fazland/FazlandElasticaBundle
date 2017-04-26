<?php

namespace Fazland\ElasticaBundle\Tests\Command;

use Fazland\ElasticaBundle\Command\PopulateCommand;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Elastica\Type;
use Fazland\ElasticaBundle\Index\IndexManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PopulateCommandTest extends TestCase
{
    /**
     * @var PopulateCommand
     */
    private $populateCommand;

    /**
     * @var ObjectProphecy|IndexManager
     */
    private $indexManager;

    /**
     * @var ObjectProphecy|EventDispatcher
     */
    private $eventDispatcher;

    protected function setUp()
    {
        $this->indexManager = $this->prophesize(IndexManager::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcher::class);

        $this->populateCommand = new PopulateCommand($this->indexManager->reveal(), $this->eventDispatcher->reveal());
    }

    public function testRunResetMustNotBeCalledWhenNoResetOptionIsPassed()
    {
        $index1 = $this->prophesize(Index::class);
        $index1->populate(Argument::type('array'))->shouldBeCalled();

        $index1->reset()->shouldNotBeCalled();

        $this->indexManager->getIndex('index')->willReturn($index1->reveal());
        $this->populateCommand->run(new ArrayInput(['--no-reset' => true, '--index' => 'index']), new NullOutput());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRunThrowExceptionIfTypeIsPassedWithoutAnIndex()
    {
        $this->populateCommand->run(new ArrayInput(['--type' => 'type1']), new NullOutput());
    }

    public function testRunPopulateAllIndexesWithoutAnyIndexAndTypeDefined()
    {
        $index1 = $this->prophesize(Index::class);
        $index2 = $this->prophesize(Index::class);

        $index1->populate(["no-reset" => false, "ignore_errors" => false, "offset" => null, "size" => null])->shouldBeCalled();
        $index2->populate(["no-reset" => false, "ignore_errors" => false, "offset" => null, "size" => null])->shouldBeCalled();

        $this->indexManager->getAllIndexes()->willReturn([
            $index1->reveal(),
            $index2->reveal(),
        ]);

        $this->populateCommand->run(new ArrayInput([]), new NullOutput());
    }

    public function testRunIfPassedBatchSizeIsPassedToPopulatesOptions()
    {
        $index1 = $this->prophesize(Index::class);

        $index1->populate(Argument::withKey('batch_size'))->shouldBeCalled();
        $this->indexManager->getIndex('index1')->willReturn($index1->reveal());

        $this->populateCommand->run(new ArrayInput(['--index' => 'index1', '--batch-size' => '10']), new NullOutput());
    }

    public function testRunGetTypeShouldBeCalledIfTypeOptionsIsPassed()
    {
        $index1 = $this->prophesize(Index::class);
        $type1 = $this->prophesize(Type::class);

        $type1->populate(Argument::type('array'))->shouldBeCalled();
        $index1->getType(Argument::exact('type1'))->willReturn($type1->reveal());
        $this->indexManager->getIndex('index1')->willReturn($index1->reveal());

        $this->populateCommand->run(new ArrayInput(['--index' => 'index1', '--type' => 'type1']), new NullOutput());
    }

    /**
     * @requires function Symfony\Component\Console\Input\ArrayInput::setStream
     */
    public function testRunDoNothingIfUserDontWantResetIndex()
    {
        $this->indexManager->getAllIndexes()->shouldNotBeCalled();

        $arrayInput = new ArrayInput(['--no-reset' => false, '--offset' => 100]);

        $in = fopen('php://memory', 'w');
        fwrite($in, "n\n");
        rewind($in);
        $arrayInput->setStream($in);

        $out = fopen('php://memory', 'w');

        $this->populateCommand->run($arrayInput, new StreamOutput($out));
        rewind($out);

        $this->assertEquals(file_get_contents(__DIR__.'/../fixtures/populate_output1.txt'), stream_get_contents($out));
    }
}
