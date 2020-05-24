<?php

namespace Tkotosz\CliAppWrapper;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tkotosz\CliAppWrapperApi\Application;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;
use Tkotosz\CliAppWrapperApi\ApplicationFactory;
use Tkotosz\ComposerWrapper\Composer;

class CliAppWrapper
{
    public function createWrappedApplication(ApplicationConfig $config): Application
    {
        $output = new ConsoleOutput();

        try {
            $workingDir = $this->locateWorkingDir($config);
            $composerFilePath = $workingDir . DIRECTORY_SEPARATOR . $config->appDir() . DIRECTORY_SEPARATOR . 'app.json';
            $composer = new Composer(new Filesystem(), new ArgvInput(), $output, $composerFilePath);
            $applicationManager = new ApplicationManager($composer, $config, $workingDir);

            if (!$this->autoloadWrappedApplication($config, $workingDir)) {
                return new AppInitApplication($applicationManager);
            }

            return $this->createApplication($applicationManager);
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(255);
        }
    }

    private function createApplication(ApplicationManager $applicationManager): Application
    {
        $appFactory = $applicationManager->getApplicationConfig()->appFactory();

        if (!class_exists($appFactory)) {
            throw new RuntimeException(
                sprintf('Application Factory class "%s" not found', $appFactory)
            );
        }

        if (!is_subclass_of($appFactory, ApplicationFactory::class)) {
            throw new RuntimeException(
                sprintf('Application Factory "%s" must implement "%s"', $appFactory, ApplicationFactory::class)
            );
        }

        return $appFactory::create($applicationManager);
    }

    private function autoloadWrappedApplication(ApplicationConfig $config, string $workingDir): bool
    {
        $autoload = $workingDir . '/' . $config->appDir() . '/autoload.php';

        if (!file_exists($autoload)) {
            return false;
        }

        require $autoload;

        return true;
    }

    private function locateWorkingDir(ApplicationConfig $config): string
    {
        $mode = $_SERVER['argv'][1] ?? null;

        // global was requested specifically
        if ($config->isGlobalModeEnabled() && $mode === 'global') {
            unset($_SERVER['argv'][1]);
            $_SERVER['argv'] = array_values($_SERVER['argv']);
            return $config->getGlobalWorkingDir();
        }

        return $config->getLocalWorkingDir();
    }
}