<?php

namespace Fazland\ElasticaBundle\Command;

use Fazland\ElasticaBundle\Elastica\Index;
use Fazland\ElasticaBundle\Event\IndexPopulateEvent;
use Fazland\ElasticaBundle\Event\TypePopulateEvent;
use Fazland\ElasticaBundle\Index\IndexManager;
use Fazland\ElasticaBundle\Index\Resetter;
use Fazland\ElasticaBundle\Provider\ProviderRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Populate the search index.
 */
class PopulateCommand extends ContainerAwareCommand
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var ProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @see Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('fazland:elastica:populate')
            ->addOption('index', null, InputOption::VALUE_OPTIONAL, 'The index to repopulate')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The type to repopulate')
            ->addOption('no-reset', null, InputOption::VALUE_NONE, 'Do not reset index before populating')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Start indexing at offset', 0)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep time between persisting iterations (microseconds)', 0)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Index packet size (overrides provider config option)')
            ->addOption('ignore-errors', null, InputOption::VALUE_NONE, 'Do not stop on errors')
            ->addOption('no-overwrite-format', null, InputOption::VALUE_NONE, 'Prevent this command from overwriting ProgressBar\'s formats')
            ->setDescription('Populates search indexes from providers')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->indexManager = $this->getContainer()->get('fazland_elastica.index_manager');
        $this->providerRegistry = $this->getContainer()->get('fazland_elastica.provider_registry');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $index = $input->getOption('index');
        $type = $input->getOption('type');
        $reset = ! $input->getOption('no-reset');
        $options = [
            'ignore_errors' => $input->getOption('ignore-errors'),
            'offset' => $input->getOption('offset'),
            'sleep' => $input->getOption('sleep'),
        ];
        if ($input->getOption('batch-size')) {
            $options['batch_size'] = (int) $input->getOption('batch-size');
        }

        if (null === $index && null !== $type) {
            throw new \InvalidArgumentException('Cannot specify type option without an index.');
        }

        if ($reset && $input->getOption('offset') &&
            ! $this->io->confirm('You chose to reset the index and start indexing with an offset. Do you really want to do that?')) {
            return;
        }

        if (null !== $index) {
            if (null !== $type) {
                $this->populateIndexType($index, $type, $reset, $options);
            } else {
                $this->populateIndex($index, $reset, $options);
            }
        } else {
            $indexes = array_keys($this->indexManager->getAllIndexes());

            foreach ($indexes as $index) {
                $this->populateIndex($index, $reset, $options);
            }
        }
    }

    /**
     * Recreates an index, populates its types, and refreshes the index.
     *
     * @param string $index
     * @param bool $reset
     * @param array $options
     */
    private function populateIndex($index, $reset, $options)
    {
        $event = new IndexPopulateEvent($index, $reset, $options);
        $this->dispatcher->dispatch(IndexPopulateEvent::PRE_INDEX_POPULATE, $event);

        if ($event->isReset()) {
            $this->io->note(sprintf('Resetting %s', $index));
            $this->resetter->resetIndex($index, true);
        }

        $types = array_keys($this->providerRegistry->getIndexProviders($index));
        foreach ($types as $type) {
            $this->populateIndexType($index, $type, false, $event->getOptions());
        }

        $this->dispatcher->dispatch(IndexPopulateEvent::POST_INDEX_POPULATE, $event);

        $this->refreshIndex($index);
    }

    /**
     * Deletes/remaps an index type, populates it, and refreshes the index.
     *
     * @param string $index
     * @param string $type
     * @param bool $reset
     * @param array $options
     */
    private function populateIndexType($index, $type, $reset, $options)
    {
        $event = new TypePopulateEvent($index, $type, $reset, $options);
        $this->dispatcher->dispatch(TypePopulateEvent::PRE_TYPE_POPULATE, $event);

        if ($event->isReset()) {
            $this->io->note(sprintf('Resetting %s/%s', $index, $type));
            $this->resetter->resetIndexType($index, $type);
        }

        $provider = $this->providerRegistry->getProvider($index, $type);

        $progressBar = null;
        $provider->populate(function ($increment, $totalObjects, $message = null) use (&$progressBar) {
            if (null === $progressBar) {
                $progressBar = $this->io->createProgressBar($totalObjects);
            }

            if (null !== $message) {
                $progressBar->setMessage($message);
            }

            $progressBar->advance($increment);
        }, $event->getOptions());

        $this->dispatcher->dispatch(TypePopulateEvent::POST_TYPE_POPULATE, $event);

        if ($progressBar instanceof ProgressBar) {
            $progressBar->clear();
        }

        $this->refreshIndex($index, false);
    }

    /**
     * Refreshes an index.
     *
     * @param Index $index
     * @param bool $postPopulate
     */
    private function refreshIndex(Index $index, $postPopulate = true)
    {
        if ($postPopulate) {
            $index->getAliasStrategy()->finalize();
        }

        $this->io->note(sprintf('Refreshing %s', $index));
        $index->refresh();
    }
}
