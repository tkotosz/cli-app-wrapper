<?php

use Tkotosz\CliAppWrapper\CliAppWrapper;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;

require __DIR__ . '/vendor/autoload.php';

$config =  [
    'app_name' => 'Foo App',
    'app_package' => 'tkotosz/fooapp-src',
    'app_version' => '*',
    'app_dir' => '.fooapp',
    'app_factory' => 'Tkotosz\\FooApp\\ApplicationFactory',
    'app_extensions' =>
        [
            'package_type' => 'tkotosz-fooapp-extension',
            'extension_class_config_field' => 'tkotosz-fooapp-extension-class',
        ],
    'global_mode_enabled' => true
];

(new CliAppWrapper)
    ->createWrappedApplication(ApplicationConfig::fromArray($config))
    ->run();