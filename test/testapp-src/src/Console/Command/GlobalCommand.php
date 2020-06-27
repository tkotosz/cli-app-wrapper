<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;

class GlobalCommand extends Command
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
            ->setName('global')
            ->setDescription('Allows running commands in the global application dir')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // no-op, the application wrapper will automatically resolve the working dir and removes the "global" keyword
        return 0;
    }
}