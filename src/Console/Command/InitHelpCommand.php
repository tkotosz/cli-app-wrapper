<?php

namespace Tkotosz\CliAppWrapper\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tkotosz\CliAppWrapper\ApplicationManager;

class InitHelpCommand extends Command
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
        $this->setName('help');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $appConfig = $this->applicationManager->getApplicationConfig();
        $localInstallDir = $this->applicationManager->getLocalApplicationDirectory();
        $globalInstallDir = $this->applicationManager->getGlobalApplicationDirectory();

        if ($this->applicationManager->getWorkingMode()->isGlobal()) {
            $io->warning('Application is not yet initialized globally');
            $output->writeln('<comment>Available commands:</comment>');
            $output->writeln(
                sprintf(
                    '  <info>%s</info>%s%s',
                    $appConfig->isSingleModeApplication() ? 'init' : 'global init',
                    str_repeat(' ', 2),
                    sprintf('initialize the application globally (%s)', $globalInstallDir)
                )
            );
        } else {
            $io->warning('Application is not yet initialized');
            $output->writeln('<comment>Available commands:</comment>');
            $output->writeln(
                sprintf(
                    '  <info>%s</info>%s%s',
                    'init',
                    str_repeat(' ', 14),
                    sprintf('initialize the application locally (%s)', $localInstallDir)
                )
            );
            if ($appConfig->isGlobalModeEnabled()) {
                $output->writeln(
                    sprintf(
                        '  <info>%s</info>%s%s',
                        'global [command]',
                        str_repeat(' ', 2),
                        sprintf('run the [command] command globally (%s)', $globalInstallDir)
                    )
                );
            }
        }

        return 0;
    }

    public function setCommand()
    {
        return $this;
    }
}
