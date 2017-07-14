<?php

namespace Fazland\ElasticaBundle\Command;

use Fazland\ElasticaBundle\Console\ConsoleStyle;
use Fazland\ElasticaBundle\Elastica\Type;
use Fazland\ElasticaBundle\Event\Events;
use Fazland\ElasticaBundle\Event\TypePopulateEvent;
use Fazland\ElasticaBundle\Index\IndexManager;
use Fazland\ElasticaBundle\Provider\CountAwareProviderInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Populate the search index.
 */
class PopulateCommand extends ContainerAwareCommand
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(IndexManager $indexManager, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();

        $this->indexManager = $indexManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function configure()
    {
        $this
            ->setName('fazland:elastica:populate')
            ->addOption('index', null, InputOption::VALUE_REQUIRED, 'The index to repopulate')
            ->addOption('no-index', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Exclude index from population')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The type to repopulate')
            ->addOption('no-reset', null, InputOption::VALUE_NONE, 'Do not reset index before populating')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Start indexing at offset')
            ->addOption('size', null, InputOption::VALUE_REQUIRED, 'Objects to persist')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep time between persisting iterations (microseconds)', 0)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Index packet size (overrides provider config option)')
            ->addOption('ignore-errors', null, InputOption::VALUE_NONE, 'Do not stop on errors')
            ->setDescription('Populates search indexes from providers')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleStyle($input, $output);

        $io->title('ES Populate');

        $index = $input->getOption('index');
        $type = $input->getOption('type');
        $options = [
            'no-reset' => $noReset = $input->getOption('no-reset'),
            'ignore_errors' => $input->getOption('ignore-errors'),
            'offset' => null,
            'size' => null,
        ];

        if ($batchSize = $input->getOption('batch-size')) {
            $options['batch_size'] = (int) $input->getOption('batch-size');
        }

        if ($offset = $input->getOption('offset')) {
            $options['offset'] = (int) $input->getOption('offset');
        }

        if ($size = $input->getOption('size')) {
            $options['size'] = (int) $input->getOption('size');
        }

        if ($sleep = $input->getOption('sleep')) {
            $options['sleep'] = (int) $input->getOption('sleep');
        }

        if (null === $index && null !== $type) {
            throw new \InvalidArgumentException('Cannot specify type option without an index.');
        }

        if (! $noReset && $input->getOption('offset') &&
            ! $io->confirm('You chose to reset the index and start indexing with an offset. Do you really want to do that?')) {
            return;
        }

        $this->eventDispatcher
            ->addListener(Events::PRE_TYPE_POPULATE, function (TypePopulateEvent $event) use ($io, $options) {
                /** @var Type $type */
                $type = $event->getType();
                $io->note(sprintf('Populating %s/%s', $type->getIndex()->getName(), $type->getName()));

                $provider = $type->getProvider();
                $io->progressStart($provider instanceof CountAwareProviderInterface ? $provider->count($options['offset'], $options['size']) : null, 'Populating...');
            }, -100);
        $this->eventDispatcher
            ->addListener(Events::POST_TYPE_POPULATE, function () use ($io) {
                $io->progressFinish('Refreshing...');
                $io->note('Refreshing index');
            });
        $this->eventDispatcher
            ->addListener(Events::TYPE_POPULATE, function () use ($io) {
                $io->progressAdvance();
            });

        if (null !== $index) {
            $index = $this->indexManager->getIndex($index);

            if (null !== $type) {
                unset($options['no-reset']);
                $index->getType($type)->populate($options);
            } else {
                $index->populate($options);
            }
        } else {
            foreach ($this->indexManager->getAllIndexes() as $name => $index) {
                if (in_array($name, $input->getOption('no-index'))) {
                    continue;
                }
                $index->populate($options);
            }
        }
    }
}
