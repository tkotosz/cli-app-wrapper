<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;

class ExtensionRemoveCommand extends Command
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
        $this->setName('extension:remove')
            ->addArgument('extension', InputArgument::REQUIRED, 'Extension to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->applicationManager->removeExtension($input->getArgument('extension') ?? '')->toInt();
    }
}