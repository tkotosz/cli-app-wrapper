<?php

use Tkotosz\CliAppWrapper\CliAppWrapper;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;

require __DIR__ . '/vendor/autoload.php';

$config =  [
    'app_name' => 'Test App',
    'app_package' => 'tkotosz/testapp-src',
    'app_version' => '*',
    'app_dir' => '.testapp',
    'app_factory' => 'Tkotosz\\TestApp\\ApplicationFactory',
    'app_extensions' =>
        [
            'package_type' => 'tkotosz-testapp-extension',
            'extension_class_config_field' => 'tkotosz-testapp-extension-class',
        ],
    'repositories' => [
        'test' => [
            'type' => 'path',
            'url' => 'test/*'
        ]
    ],
    'global_mode_enabled' => true
];

(new CliAppWrapper)
    ->createWrappedApplication(ApplicationConfig::fromArray($config))
    ->run();