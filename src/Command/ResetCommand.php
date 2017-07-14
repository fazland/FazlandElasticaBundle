<?php

namespace Fazland\ElasticaBundle\Command;

use Fazland\ElasticaBundle\Console\ConsoleStyle;
use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\IndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reset search indexes.
 */
class ResetCommand extends Command
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    public function __construct(IndexManager $indexManager)
    {
        parent::__construct();

        $this->indexManager = $indexManager;
    }

    protected function configure()
    {
        $this
            ->setName('fazland:elastica:reset')
            ->addOption('index', null, InputOption::VALUE_REQUIRED, 'The index to reset')
            ->addOption('no-index', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'The index to reset')
            ->setDescription('Reset search indexes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleStyle($input, $output);

        if (null !== ($index = $input->getOption('index'))) {
            $indexes = [ $index => $this->indexManager->getIndex($index) ];
            $exclude = [];
        } else {
            $indexes = $this->indexManager->getAllIndexes();
            $exclude = $input->getOption('no-index');
        }

        /** @var Index $index */
        foreach ($indexes as $name => $index) {
            if (in_array($name, $exclude)) {
                continue;
            }

            $io->note('Resetting '.$index->getName());
            $index->reset();
        }
    }
}
