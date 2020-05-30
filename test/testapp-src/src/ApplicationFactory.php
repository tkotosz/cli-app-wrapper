<?php

namespace Tkotosz\TestApp;

use Tkotosz\CliAppWrapperApi\Application as ApplicationInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;
use Tkotosz\CliAppWrapperApi\ApplicationFactory as ApplicationFactoryInterface;

class ApplicationFactory implements ApplicationFactoryInterface
{
    public static function create(ApplicationManager $applicationManager): ApplicationInterface
    {
        return new Application($applicationManager);
    }
}