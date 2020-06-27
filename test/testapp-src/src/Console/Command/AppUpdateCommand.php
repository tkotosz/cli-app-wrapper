<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;

class AppUpdateCommand extends Command
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
            ->setName('app:update')
            ->setDescription('Update Application')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->applicationManager->update()->toInt();
    }
}