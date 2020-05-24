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
        if (count($_SERVER['argv']) > 2 || !isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] !== 'init') {
            echo "Application is not yet initialized" . PHP_EOL;
            echo "Please run init or global init" . PHP_EOL;
            echo "Help:" . PHP_EOL;
            echo "  init          init locally" . PHP_EOL;
            echo "  global init   init globally" . PHP_EOL;

            exit(0);
        }

        $result = $this->applicationManager->init();

        if ($result !== 0) {
            echo "Init FAILED" . PHP_EOL;
        } else {
            echo "All OK, Init DONE" . PHP_EOL;
        }

        exit($result);
    }
}