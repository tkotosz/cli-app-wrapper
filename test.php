<?php

use Tkotosz\CliAppWrapper\ApplicationConfig;
use Tkotosz\CliAppWrapper\CliAppWrapper;

require __DIR__ . '/vendor/autoload.php';

$config =  [
    'app_name' => 'Test App 2.0',
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
    'global_mode_enabled' => true,
    'local_mode_enabled' => true,
    "app_executable_name" => "test",
    "github_user" => "tkotosz",
    "github_repository" => "test",
    'local_working_directory_resolvers' => ['git', 'cwd']
];

(new CliAppWrapper)
    ->createWrappedApplication(ApplicationConfig::fromArray($config))
    ->run();
