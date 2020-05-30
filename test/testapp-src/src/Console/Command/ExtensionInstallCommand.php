<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;

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
            ->addArgument('extension', InputArgument::OPTIONAL, 'Extension to install');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('extension')) {
            return $this->applicationManager->installExtension($input->getArgument('extension'));
        } else {
            return $this->applicationManager->updateExtensions();
        }
    }
}