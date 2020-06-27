<?php

namespace Tkotosz\TestApp;

use Tkotosz\CliAppWrapperApi\Api\V1\Application as ApplicationInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationFactory as ApplicationFactoryInterface;

class ApplicationFactory implements ApplicationFactoryInterface
{
    public static function create(ApplicationManager $applicationManager): ApplicationInterface
    {
        return new Application($applicationManager);
    }
}