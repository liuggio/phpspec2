<?php

namespace PHPSpec2\Formatter;

use PHPSpec2\Console\IO;
use PHPSpec2\Formatter\Presenter\PresenterInterface;
use PHPSpec2\Listener\StatisticsCollector;

use PHPSpec2\Event\SuiteEvent;
use PHPSpec2\Event\SpecificationEvent;
use PHPSpec2\Event\ExampleEvent;

class ProgressFormatter implements FormatterInterface
{
    private $io;
    private $presenter;
    private $stats;

    public static function getSubscribedEvents()
    {
        $events = array('afterExample', 'afterSuite');

        return array_combine($events, $events);
    }

    public function setIO(IO $io)
    {
        $this->io = $io;
    }

    public function setPresenter(PresenterInterface $presenter)
    {
        $this->presenter = $presenter;
    }

    public function setStatisticsCollector(StatisticsCollector $stats)
    {
        $this->stats = $stats;
    }

    public function afterExample(ExampleEvent $event)
    {
        $total  = $this->stats->getEventsCount();
        $counts = $this->stats->getCountsHash();

        $progress = '';
        foreach ($counts as $status => $count) {
            $percent = round($count / ($total / 100), 0, PHP_ROUND_HALF_EVEN);
            $length  = round($percent / 2, 0, PHP_ROUND_HALF_EVEN);
            $text    = $percent.'%';

            if ($length > strlen($text) + 2) {
                $text = str_pad($text, $length, ' ', STR_PAD_BOTH);
            } else {
                $text = str_pad('', $length, ' ');
            }

            $progress .= sprintf("<$status-bg>%s</$status-bg>", $text);
        }

        $this->io->writeTemp($progress.' : '.$total);
        $this->printException($event, 2);
    }

    public function afterSuite(SuiteEvent $event)
    {
        $this->io->freezeTemp();
        $this->io->writeln();

        $counts = array();
        foreach ($this->stats->getCountsHash() as $type => $count) {
            if ($count) {
                $counts[] = sprintf('<%s>%d %s</%s>', $type, $count, $type, $type);
            }
        }
        $this->io->write(sprintf("\n%d examples ", $this->stats->getEventsCount()));
        if (count($counts)) {
            $this->io->write(sprintf("(%s)", implode(', ', $counts)));
        }

        $this->io->writeln(sprintf("\n%sms", round($event->getTime() * 1000)));
    }

    protected function printException(ExampleEvent $event)
    {
        if (null === $exception = $event->getException()) {
            return;
        }

        $title = str_pad($event->getSpecification()->getTitle(), 50, ' ', STR_PAD_RIGHT);
        // TODO: add cause to exception interface
        $exception->cause = $event->getExample()->getFunction();
        $message = $this->presenter->presentException($exception, $this->io->isVerbose());

        if (ExampleEvent::FAILED === $event->getResult()) {
            $this->io->writeln(sprintf('<failed-bg>%s</failed-bg>', $title));
            $this->io->writeln(sprintf(
                '<lineno>%4d</lineno>  <failed>✘ %s</failed>',
                $event->getExample()->getFunction()->getStartLine(),
                $event->getExample()->getTitle()
            ));
            $this->io->writeln(sprintf('<failed>%s</failed>', lcfirst($message)), 6);
        } else {
            $this->io->writeln(sprintf('<broken-bg>%s</broken-bg>', $title));
            $this->io->writeln(sprintf(
                '<lineno>%4d</lineno>  <broken>! %s</broken>',
                $event->getExample()->getFunction()->getStartLine(),
                $event->getExample()->getTitle()
            ));
            $this->io->writeln(sprintf('<broken>%s</broken>', lcfirst($message)), 6);
        }

        $this->io->writeln();
    }
}