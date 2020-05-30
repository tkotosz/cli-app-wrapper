<?php

namespace Tkotosz\TestApp;

use Symfony\Component\Console\Application;

interface Extension
{
    public function addCommands(Application $application): void;
}