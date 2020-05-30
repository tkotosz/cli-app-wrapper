<?php

namespace Tkotosz\CliAppWrapper;

use Tkotosz\CliAppWrapperApi\Application;

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
        return 0;
    }

    public function run(): void
    {
        if (!$this->isInitRequest($_SERVER['argv'])) {
            echo $this->buildHelpMessage();
            exit(0);
        }

        $result = $this->applicationManager->init();

        if ($result !== 0) {
            echo "Application Initialization FAILED" . PHP_EOL;
        } else {
            echo "Application Successfully Initialized" . PHP_EOL;
        }

        exit($result);
    }

    private function buildHelpMessage(): string
    {
        $appConfig = $this->applicationManager->getApplicationConfig();
        $localInstallDir = $this->applicationManager->getLocalApplicationDirectory();
        $globalInstallDir = $this->applicationManager->getGlobalApplicationDirectory();

        $helpMessage = '';
        if ($this->applicationManager->getWorkingMode()->isGlobal()) {
            $helpMessage .= "Application is not yet initialized globally" . PHP_EOL;
            $helpMessage .= "Please run the global init command to initialize the application globally" . PHP_EOL;
            $helpMessage .= "Help:" . PHP_EOL;
            $helpMessage .= sprintf('  global init   init globally (%s)', $globalInstallDir) . PHP_EOL;
        } else {
            $helpMessage .= "Application is not yet initialized" . PHP_EOL;
            $helpMessage .= "Please run the init command to initialize the application locally" . PHP_EOL;
            if ($appConfig->isGlobalModeEnabled()) {
                $helpMessage .= "Alternatively you can switch to global mode using the global keyword" . PHP_EOL;
            }
            $helpMessage .= "Help:" . PHP_EOL;
            $helpMessage .= sprintf('  init               init locally (%s)', $localInstallDir) . PHP_EOL;
            if ($appConfig->isGlobalModeEnabled()) {
                $helpMessage .= sprintf('  global [command]   run [command] globally (%s)', $globalInstallDir) . PHP_EOL;
            }
        }

        return $helpMessage;
    }

    private function isInitRequest(array $request): bool
    {
        return count($request) == 2 && $request[1] === 'init';
    }
}