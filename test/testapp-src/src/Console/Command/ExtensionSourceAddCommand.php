<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;
use Tkotosz\CliAppWrapperApi\ExtensionSource;

class ExtensionSourceAddCommand extends Command
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
            ->setName('extension:source:add')
            ->setDescription('Add Extension Source')
            ->addArgument('name', InputArgument::REQUIRED, 'Source Name')
            ->addArgument('type', InputArgument::REQUIRED, 'Source Type')
            ->addArgument('url', InputArgument::REQUIRED, 'Source URL')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->applicationManager->addExtensionSource(
            ExtensionSource::fromValues(
                $input->getArgument('name'),
                $input->getArgument('type'),
                $input->getArgument('url')
            )
        );
    }
}
