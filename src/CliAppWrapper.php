<?php

namespace Tkotosz\CliAppWrapper;

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

            if (!$this->autoloadWrappedApplication($config, $workingDir)) {
                return new AppInitApplication($composer, $config, $workingDir);
            }

            return $this->createApplication($composer, $config, $workingDir);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            exit(255);
        }
    }

    private function createApplication(Composer $composer, ApplicationConfig $config, string $workingDir): Application
    {
        $appFactory = $config->appFactory();

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

        return $appFactory::create(new ApplicationManager($composer, $config, $workingDir));
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
        $local = getcwd();
        $global = $this->getHomeDir();
        $mode = $_SERVER['argv'][1] ?? null;

        // local was requested specifically
        if ($mode === 'local') {
            unset($_SERVER['argv'][1]);
            $_SERVER['argv'] = array_values($_SERVER['argv']);
            return $local;
        }

        // global was requested specifically
        if ($mode === 'global') {
            unset($_SERVER['argv'][1]);
            $_SERVER['argv'] = array_values($_SERVER['argv']);
            return $global;
        }

        // if local initialized already then lets go with that
        if (file_exists(getcwd() . '/' . $config->appDir() . '/autoload.php')) {
            return $local;
        }

        // fallback to global
        return $global;
    }

    private function getHomeDir(): string
    {
        if ($path = getenv('XDG_CONFIG_HOME')) {
            return $path;
        }

        return getenv('HOME') ?: (getenv('HOMEDRIVE') . DIRECTORY_SEPARATOR . getenv('HOMEPATH'));
    }
}