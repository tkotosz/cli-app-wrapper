<?php

namespace Tkotosz\TestApp;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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

    public function init(): int
    {
        $consoleApp = new \Symfony\Component\Console\Application(
            $this->applicationManager->getApplicationConfig()->appName(),
            $this->applicationManager->getApplicationConfig()->appVersion()
        );

        $consoleApp->add(new class extends Command {
            protected function configure()
            {
                $this->setName('init');
            }

            protected function execute(InputInterface $input, OutputInterface $output)
            {
                $questionHelper = new SymfonyQuestionHelper();

                $result = $questionHelper->ask($input, $output, new Question('How are you?', 'I am fine, thanks.'));

                return ($result === 'I am fine, thanks.') ? 0 : 1;
            }
        });

        $consoleApp->setCatchExceptions(true);
        $consoleApp->setAutoExit(false);
        $consoleApp->setDefaultCommand('init');

        return $consoleApp->run();
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