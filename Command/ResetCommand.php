<?php

namespace Fazland\ElasticaBundle\Command;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Index\IndexManager;
use Fazland\ElasticaBundle\Index\Resetter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addOption('index', null, InputOption::VALUE_OPTIONAL, 'The index to reset')
            ->setDescription('Reset search indexes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $index = $input->getOption('index');
        $indexes = null === $index ? $this->indexManager->getAllIndexes() : [$this->indexManager->getIndex($index)];

        /** @var Index $index */
        foreach ($indexes as $index) {
            $io->note('Resetting '.$index->getName());
            $index->reset();
        }
    }
}
