<?php

namespace Tkotosz\TestApp;

use Tkotosz\TestApp\Console\Command\ExtensionInstallCommand;
use Tkotosz\TestApp\Console\Command\ExtensionListCommand;
use Tkotosz\TestApp\Console\Command\ExtensionRemoveCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceAddCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceListCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceRemoveCommand;
use Tkotosz\TestApp\Console\Command\GlobalCommand;
use Tkotosz\TestApp\Console\Command\HelloWorldCommand;
use Tkotosz\CliAppWrapperApi\Application as ApplicationInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;

class Application implements ApplicationInterface
{
    /** @var ApplicationManager */
    private $applicationManager;

    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
    }

    public function run(): void
    {
        $consoleApp = new \Symfony\Component\Console\Application(
            $this->applicationManager->getApplicationConfig()->appName(),
            $this->applicationManager->getApplicationConfig()->appVersion()
        );

        $consoleApp->add(new GlobalCommand($this->applicationManager));

        $consoleApp->add(new ExtensionSourceListCommand($this->applicationManager));
        $consoleApp->add(new ExtensionSourceAddCommand($this->applicationManager));
        $consoleApp->add(new ExtensionSourceRemoveCommand($this->applicationManager));

        $consoleApp->add(new ExtensionListCommand($this->applicationManager));
        $consoleApp->add(new ExtensionInstallCommand($this->applicationManager));
        $consoleApp->add(new ExtensionRemoveCommand($this->applicationManager));

        $consoleApp->add(new HelloWorldCommand());

        foreach ($this->applicationManager->findInstalledExtensions() as $extension) {
            $extensionClass = $extension->extensionClass();
            $extension = new $extensionClass;
            $extension->addCommands($consoleApp);
        }

        $consoleApp->run();
    }
}