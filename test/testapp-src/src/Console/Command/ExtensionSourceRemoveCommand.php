<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;

class ExtensionSourceRemoveCommand extends Command
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
            ->setName('extension:source:remove')
            ->setDescription('Remove Extension Source')
            ->addArgument('name', InputArgument::REQUIRED, 'Source Name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->applicationManager->removeExtensionSource(
            $input->getArgument('name')
        )->toInt();
    }
}
