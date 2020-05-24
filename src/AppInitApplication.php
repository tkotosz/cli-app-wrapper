<?php

namespace Tkotosz\CliAppWrapper;

use RuntimeException;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;
use Tkotosz\CliAppWrapperApi\Application;
use Tkotosz\ComposerWrapper\Composer;

class AppInitApplication implements Application
{
    /** @var Composer */
    private $composer;

    /** @var ApplicationConfig */
    private $config;

    /** @var string */
    private $workingDir;

    public function __construct(Composer $composer, ApplicationConfig $config, string $workingDir)
    {
        $this->composer = $composer;
        $this->config = $config;
        $this->workingDir = $workingDir;
    }

    public function run(): void
    {
        if (count($_SERVER['argv']) > 2 || !isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] !== 'init') {
            echo "Application is not yet initialized" . PHP_EOL;
            echo "Please run init or local init or global init" . PHP_EOL;
            echo "Help:" . PHP_EOL;
            echo "  init          init locally" . PHP_EOL;
            echo "  local init    init locally" . PHP_EOL;
            echo "  global init   init global" . PHP_EOL;

            exit(0);
        }

        $result = $this->composer->init($this->workingDir . DIRECTORY_SEPARATOR . $this->config->appDir());
        if ($result !== 0) {
            throw new RuntimeException('Could not initialize the application');
        }

        $config = $this->composer->getComposerConfig();
        foreach ($this->config->repositories() as $repository) {
            $config = $config->addRepository($repository['type'], $repository['url']);
        }
        $config = $config->addProvide('tkotosz/cli-app-wrapper-api', '*');

        $result = $this->composer->changeComposerConfig($config);
        if ($result !== 0) {
            throw new RuntimeException('Could not initialize the application');
        }

        $this->composer->installPackage($this->config->appPackage(), $this->config->appVersion());
        if ($result !== 0) {
            throw new RuntimeException('Could not initialize the application');
        }

        echo "All OK, Init DONE" . PHP_EOL;
        exit($result);
    }
}