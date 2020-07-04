<?php

namespace Tkotosz\CliAppWrapper;

use InvalidArgumentException;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\WorkingMode;

class ApplicationConfig
{
    private const REQUIRED_CONFIGS = [
        'app_name',
        'app_package',
        'app_version',
        'app_dir',
        'app_factory',
        'app_executable_name'
    ];

    private const DEFAULT_CONFIGS = [
        'repositories' => [],
        'global_mode_enabled' => false,
        'local_mode_enabled' => true,
        'app_extensions' => [
            'package_type' => null,
            'extension_class_config_field' => null
        ],
        'local_working_directory_resolvers' => ['cwd']
    ];

    /** @var array */
    private $config;

    public static function fromArray(array $config): self
    {
        foreach (self::REQUIRED_CONFIGS as $key) {
            if (empty($config[$key])) {
                throw new InvalidArgumentException(sprintf('Config "%s" required but not found', $key));
            }
        }

        if (!$config['global_mode_enabled'] && !$config['local_mode_enabled']) {
            throw new InvalidArgumentException('Cannot disable both local and global mode');
        }

        return new self($config + self::DEFAULT_CONFIGS);
    }

    public function appName(): string
    {
        return $this->config['app_name'];
    }

    public function appPackage(): string
    {
        return $this->config['app_package'];
    }

    public function appVersion(): string
    {
        return $this->config['app_version'];
    }

    public function appExecutableName(): string
    {
        return $this->config['app_executable_name'];
    }

    public function githubUser(): ?string
    {
        return $this->config['github_user'] ?? null;
    }

    public function githubRepository(): ?string
    {
        return $this->config['github_repository'] ?? null;
    }

    public function appExtensionsPackageType(): ?string
    {
        return $this->config['app_extensions']['package_type'] ?? null;
    }

    public function appExtensionsExtensionClassConfigField(): ?string
    {
        return $this->config['app_extensions']['extension_class_config_field'] ?? null;
    }

    public function appDir(): string
    {
        return $this->config['app_dir'];
    }

    public function appFactory(): string
    {
        return $this->config['app_factory'];
    }

    public function repositories(): array
    {
        return $this->config['repositories'];
    }

    public function isGlobalModeEnabled(): bool
    {
        return $this->config['global_mode_enabled'];
    }

    public function isLocalModeEnabled(): bool
    {
        return $this->config['local_mode_enabled'];
    }

    public function isSingleModeApplication(): bool
    {
        return
            ($this->isLocalModeEnabled() && !$this->isGlobalModeEnabled()) ||
            (!$this->isLocalModeEnabled() && $this->isGlobalModeEnabled());
    }

    public function defaultWorkingMode(): WorkingMode
    {
        return $this->isLocalModeEnabled() ? WorkingMode::local() : WorkingMode::global();
    }

    public function allowsExtensions(): bool
    {
        return $this->appExtensionsPackageType() !== null && $this->appExtensionsExtensionClassConfigField() !== null;
    }

    public function localWorkingDirectoryResolvers(): array
    {
        return array_filter(
            array_unique(array_merge($this->config['local_working_directory_resolvers'], ['cwd'])),
            function (string $resolver) {
                return in_array($resolver, ['git', 'cwd']); // only these 2 possible for now
            }
        );
    }

    private function __construct(array $config)
    {
        $this->config = $config;
    }
}
