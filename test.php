<?php

use Tkotosz\CliAppWrapper\CliAppWrapper;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;

require __DIR__ . '/vendor/autoload.php';

$config =  [
    'app_name' => 'Bar App',
    'app_package' => 'tkotosz/barapp-src',
    'app_version' => '*',
    'app_dir' => '.barapp',
    'app_factory' => 'Tkotosz\\BarApp\\ApplicationFactory',
    'app_extensions' =>
        [
            'package_type' => 'barapp-extension',
            'extension_class_config_field' => 'barapp-extension-class',
        ],
    'repositories' =>
        [
            0 =>
                [
                    'type' => 'path',
                    'url' => '../composertest/barapp-src/',
                ],
            1 =>
                [
                    'type' => 'path',
                    'url' => '../composertest/barapp-extensions/*',
                ],
        ],
];

(new CliAppWrapper)
    ->createWrappedApplication(ApplicationConfig::fromArray($config))
    ->run();