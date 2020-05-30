<?php

namespace Tkotosz\TestApp\DateTimeExtension\Console\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DateTimeNowCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('datetime:now')
            ->setDescription('Shows current date and time')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Format', 'Y-m-d H:i:s')
            ->addOption('timestamp', 't', InputOption::VALUE_NONE, 'Show Timestamp')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = (new DateTimeImmutable('now'));

        if ($input->getOption('timestamp')) {
            $output->writeln($now->getTimestamp());
        } else {
            $output->writeln($now->format($input->getOption('format')));
        }

        return 0;
    }
}