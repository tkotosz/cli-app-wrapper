<?php

namespace Tkotosz\CliAppWrapper\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tkotosz\CliAppWrapper\ApplicationManager;

class InitCommand extends Command
{
    /** @var ApplicationManager */
    private $applicationManager;

    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('init')
            ->addArgument('extension_list', InputArgument::OPTIONAL | InputArgument::IS_ARRAY)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->applicationManager->init($input->getArgument('extension_list') ?? []);

        if ($result !== 0) {
            $io->error('Application Initialization FAILED');
        } else {
            $io->success('Application Successfully Initialized');
        }

        return $result;
    }
}