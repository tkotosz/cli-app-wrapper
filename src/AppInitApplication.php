<?php

namespace Tkotosz\CliAppWrapper;

use Tkotosz\CliAppWrapperApi\Application;
use Tkotosz\CliAppWrapperApi\ApplicationManager as ApplicationManagerInterface;

class AppInitApplication implements Application
{
    /** @var ApplicationManagerInterface */
    private $applicationManager;

    public function __construct(ApplicationManagerInterface $applicationManager)
    {
        $this->applicationManager = $applicationManager;
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
        $localInstallDir = $appConfig->getLocalWorkingDir() . DIRECTORY_SEPARATOR . $appConfig->appDir();
        $globalInstallDir = $appConfig->getGlobalWorkingDir() . DIRECTORY_SEPARATOR . $appConfig->appDir();
        $helpMessage = '';
        $helpMessage .= "Application is not yet initialized" . PHP_EOL;
        $helpMessage .= "Please run init or global init" . PHP_EOL;
        $helpMessage .= "Help:" . PHP_EOL;
        $helpMessage .= sprintf('  init          init locally (%s)', $localInstallDir) . PHP_EOL;

        if ($appConfig->isGlobalModeEnabled()) {
            $helpMessage .= sprintf('  global init   init globally (%s)', $globalInstallDir) . PHP_EOL;
        }

        return $helpMessage;
    }

    private function isInitRequest(array $request): bool
    {
        return count($request) == 2 && $request[1] === 'init';
    }
}