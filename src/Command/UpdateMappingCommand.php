<?php

namespace Fazland\ElasticaBundle\Command;

use Fazland\ElasticaBundle\Console\ConsoleStyle;
use Fazland\ElasticaBundle\Index\IndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Send a mapping update for a given type.
 */
class UpdateMappingCommand extends Command
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
            ->setName('fazland:elastica:update-mapping')
            ->addArgument('index', InputArgument::REQUIRED, 'The index to update')
            ->addArgument('type', InputArgument::REQUIRED, 'The type to update')
            ->setDescription('Send a mapping update')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleStyle($input, $output);

        $io->title('ES Update mapping');

        $index = $input->getArgument('index');
        $type = $input->getArgument('type');

        $index = $this->indexManager->getIndex($index);

        $io->note('Sending request...');
        $index->getType($type)->sendMapping();

        $io->success('OK');
    }
}
