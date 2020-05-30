<?php

namespace Tkotosz\TestApp\DateTimeExtension;

use Symfony\Component\Console\Application;
use Tkotosz\TestApp\DateTimeExtension\Console\Command\DateTimeNowCommand;
use Tkotosz\TestApp\DateTimeExtension\Console\Command\DateTimeTodayCommand;
use Tkotosz\TestApp\Extension;

class DateTimeExtension implements Extension
{
    public function addCommands(Application $application): void
    {
        $application->add(new DateTimeNowCommand());
        $application->add(new DateTimeTodayCommand());
    }
}