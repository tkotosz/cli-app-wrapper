<?php

namespace Tkotosz\CliAppWrapper;

use Symfony\Component\Console\Application as ConsoleApplication;
use Tkotosz\CliAppWrapper\Console\Command\InitCommand;
use Tkotosz\CliAppWrapper\Console\Command\InitHelpCommand;
use Tkotosz\CliAppWrapperApi\Api\V1\Application;

class AppInitApplication implements Application
{
    /** @var ApplicationManager */
    private $applicationManager;

    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
    }

    public function init(): int
    {
        // no-op
        return 0;
    }

    public function run(): void
    {
        $consoleApp = new ConsoleApplication(
            $this->applicationManager->getApplicationConfig()->appName(),
            $this->applicationManager->getApplicationConfig()->appVersion()
        );

        $consoleApp->add(new InitHelpCommand($this->applicationManager));
        $consoleApp->add(new InitCommand($this->applicationManager));
        $consoleApp->setCatchExceptions(true);
        $consoleApp->setAutoExit(true);
        $consoleApp->setDefaultCommand('help');

        $consoleApp->run();
    }
}