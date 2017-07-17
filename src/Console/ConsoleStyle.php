<?php declare(strict_types=1);

namespace Fazland\ElasticaBundle\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleStyle extends SymfonyStyle
{
    /**
     * @var ProgressBar|ProgressIndicator
     */
    private $progress;

    public function progressStart($max = 0, $message = null)
    {
        if (! $max && class_exists(ProgressIndicator::class)) {
            $this->progress = new ProgressIndicator($this);
            $this->progress->start($message);
        } else {
            parent::progressStart($max);
        }
    }

    public function progressAdvance($step = 1)
    {
        if (null !== $this->progress) {
            $this->progress->advance($step);
        } else {
            parent::progressAdvance($step);
        }
    }

    public function progressFinish($message = null)
    {
        if (null !== $this->progress) {
            $this->progress->finish($message);
            $this->newLine(2);

            $this->progress = null;
        } else {
            parent::progressFinish();
        }
    }
}
