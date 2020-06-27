<?php

namespace Tkotosz\TestApp;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Tkotosz\TestApp\Console\Command\AppUpdateCommand;
use Tkotosz\TestApp\Console\Command\ExtensionInstallCommand;
use Tkotosz\TestApp\Console\Command\ExtensionListCommand;
use Tkotosz\TestApp\Console\Command\ExtensionRemoveCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceAddCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceListCommand;
use Tkotosz\TestApp\Console\Command\ExtensionSourceRemoveCommand;
use Tkotosz\TestApp\Console\Command\GlobalCommand;
use Tkotosz\TestApp\Console\Command\HelloWorldCommand;
use Tkotosz\CliAppWrapperApi\Api\V1\Application as ApplicationInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;

class Application implements ApplicationInterface
{
    /** @var ApplicationManager */
    private $applicationManager;

    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
    }

    public function init(): int
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        $questionHelper = new SymfonyQuestionHelper();

        $extensions = [];
        foreach ($this->applicationManager->findInstalledExtensions() as $extension) {
            $extensions[] = $extension->name();
        }

        if (in_array('tkotosz/testapp-datetime-extension', $extensions)) {
            return 0;
        }

        $result = $questionHelper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Do you want to install datetime ext by default?')
        );

        if ($result) {
            $output->writeln('Installing datetime extension');
            return $this->applicationManager->installExtension('tkotosz/testapp-datetime-extension')->toInt();
        }

        return 0;
    }

    public function run(): void
    {
        $consoleApp = new ConsoleApplication(
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

        $consoleApp->add(new AppUpdateCommand($this->applicationManager));

        $consoleApp->add(new HelloWorldCommand());

        foreach ($this->applicationManager->findInstalledExtensions() as $extension) {
            $extensionClass = $extension->extensionClass();
            $extension = new $extensionClass;
            $extension->addCommands($consoleApp);
        }

        $consoleApp->run();
    }
}