<?php

namespace Tkotosz\CliAppWrapper;

use Exception;
use Github\Client;
use Symfony\Component\Filesystem\Filesystem;
use Tkotosz\CliAppWrapperApi\Api\V1\Application;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\WorkingMode;

class CliAppWrapper
{
    public function createWrappedApplication(ApplicationConfig $config): Application
    {
        try {
            return $this
                ->createApplicationManager($config, $this->resolveWorkingMode($config))
                ->createApplication();
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(255);
        }
    }

    private function createApplicationManager(ApplicationConfig $config, WorkingMode $workingMode): ApplicationManager
    {
        return new ApplicationManager(new Filesystem(), new Client(), new Downloader(), $config, $workingMode);
    }

    private function resolveWorkingMode(ApplicationConfig $config): WorkingMode
    {
        if ($config->isSingleModeApplication()) {
            return $config->defaultWorkingMode();
        }

        $requestedMode = $_SERVER['argv'][1] ?? null;

        if ($requestedMode === 'global') {
            unset($_SERVER['argv'][1]);
            $_SERVER['argv'] = array_values($_SERVER['argv']);
            return WorkingMode::global();
        }

        return WorkingMode::local();
    }
}