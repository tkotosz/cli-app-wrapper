<?php

namespace Tkotosz\TestApp;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
        $consoleApp = new ConsoleApplication(
            $this->applicationManager->getApplicationConfig()->appName(),
            $this->applicationManager->getApplicationConfig()->appVersion()
        );

        $consoleApp->add(new class ($this->applicationManager) extends Command {
            /** @var ApplicationManager */
            private $applicationManager;

            public function __construct(ApplicationManager $applicationManager)
            {
                $this->applicationManager = $applicationManager;
                parent::__construct();
            }

            protected function configure()
            {
                $this->setName('init');
            }

            protected function execute(InputInterface $input, OutputInterface $output)
            {
                $questionHelper = new SymfonyQuestionHelper();

                $result = $questionHelper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion('Do you want to install datetime ext by default?')
                );

                if ($result) {
                    $output->writeln('Installing datetime extension');
                    $this->applicationManager->installExtension('tkotosz/testapp-datetime-extension');
                }

                return 0;
            }
        });

        $consoleApp->setCatchExceptions(true);
        $consoleApp->setAutoExit(false);
        $consoleApp->setDefaultCommand('init');

        return $consoleApp->run();
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

        $consoleApp->add(new HelloWorldCommand());

        foreach ($this->applicationManager->findInstalledExtensions() as $extension) {
            $extensionClass = $extension->extensionClass();
            $extension = new $extensionClass;
            $extension->addCommands($consoleApp);
        }

        $consoleApp->run();
    }
}