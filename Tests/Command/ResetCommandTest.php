<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Tests\Command;

use Fazland\ElasticaBundle\Command\ResetCommand;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\IndexManager;
use Fazland\ElasticaBundle\Index\Resetter;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ResetCommandTest extends TestCase
{
    /**
     * @var ResetCommand
     */
    private $command;

    /**
     * @var IndexManager|ObjectProphecy
     */
    private $indexManager;

    public function setup()
    {
        $this->indexManager = $this->prophesize(IndexManager::class);

        $this->command = new ResetCommand($this->indexManager->reveal());
    }

    public function testResetAllIndexes()
    {
        $this->indexManager->getAllIndexes()
            ->willReturn([
                'index1' => $index1 = $this->prophesize(Index::class),
                'index2' => $index2 = $this->prophesize(Index::class),
            ]);

        $index1->getName()->willReturn('index1');
        $index1->reset()->shouldBeCalled();
        $index2->getName()->willReturn('index2');
        $index2->reset()->shouldBeCalled();

        $this->command->run(new ArrayInput([]), new NullOutput());
    }

    public function testResetIndex()
    {
        $this->indexManager->getAllIndexes()->shouldNotBeCalled();
        $this->indexManager->getIndex('index1')
            ->willReturn($index1 = $this->prophesize(Index::class));

        $index1->getName()->willReturn('index1');
        $index1->reset()->shouldBeCalled();

        $this->command->run(new ArrayInput(['--index' => 'index1']), new NullOutput());
    }
}
