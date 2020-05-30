<?php

namespace Tkotosz\TestApp\DateTimeExtension\Console\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DateTimeTodayCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('datetime:today')
            ->setDescription('Shows current date')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Format', 'Y-m-d')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            (new DateTimeImmutable('today'))->format($input->getOption('format'))
        );

        return 0;
    }
}
