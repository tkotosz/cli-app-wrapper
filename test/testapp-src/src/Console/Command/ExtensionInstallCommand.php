<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;

class ExtensionInstallCommand extends Command
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
        $this->setName('extension:install')
            ->addArgument('extension', InputArgument::REQUIRED, 'Extension to install');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->applicationManager->installExtension($input->getArgument('extension'));

        if ($result->isFailure()) {
            $io->error('Extension Installation Failed');
            return 1;
        }

        $io->success('Extension Successfully Installed');
        return 0;
    }
}