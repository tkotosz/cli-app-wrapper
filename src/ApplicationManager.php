<?php

namespace Tkotosz\CliAppWrapper;

use Composer\Package\PackageInterface;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;
use Tkotosz\CliAppWrapperApi\ApplicationManager as ApplicationManagerInterface;
use Tkotosz\CliAppWrapperApi\Extension;
use Tkotosz\CliAppWrapperApi\Extensions;
use Tkotosz\CliAppWrapperApi\ExtensionSource;
use Tkotosz\CliAppWrapperApi\ExtensionSources;
use Tkotosz\ComposerWrapper\Composer;
use Tkotosz\ComposerWrapper\Composer\Packages;

class ApplicationManager implements ApplicationManagerInterface
{
    /** @var Composer */
    private $composer;

    /** @var ApplicationConfig */
    private $config;

    /** @var string */
    private $workingDir;

    public function __construct(Composer $composer, ApplicationConfig $config, string $workingDir)
    {
        $this->composer = $composer;
        $this->config = $config;
        $this->workingDir = $workingDir;
    }

    public function getApplicationConfig(): ApplicationConfig
    {
        return $this->config;
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDir;
    }

    public function init(): int
    {
        $result = $this->composer->init($this->workingDir . DIRECTORY_SEPARATOR . $this->config->appDir());
        if ($result !== 0) {
            return $result;
        }

        $config = $this->composer->getComposerConfig();
        foreach ($this->config->repositories() as $key => $repository) {
            $config = $config->addRepository($key, $repository['type'], $repository['url']);
        }
        $config = $config->addProvide('tkotosz/cli-app-wrapper-api', '*');

        $result = $this->composer->changeComposerConfig($config);
        if ($result !== 0) {
            return $result;
        }

        $this->composer->installPackage($this->config->appPackage(), $this->config->appVersion());
        if ($result !== 0) {
            return $result;
        }

        return 0;
    }

    public function updateExtensions(): int
    {
        return $this->composer->installPackages();
    }

    public function installExtension(string $extensionPackage, string $extensionVersion = null): int
    {
        return $this->composer->installPackage($extensionPackage, $extensionVersion);
    }

    public function removeExtension(string $extensionPackage): int
    {
        return $this->composer->removePackage($extensionPackage);
    }

    public function addExtensionSource(ExtensionSource $extensionSource): int
    {
        $config = $this->composer->getComposerConfig();

        return $this->composer->changeComposerConfig(
            $config->addRepository($extensionSource->name(), $extensionSource->type(), $extensionSource->url())
        );
    }

    public function findExtensionSources(): ExtensionSources
    {
        $config = $this->composer->getComposerConfig();

        $sources = [
            ExtensionSource::fromValues('packagist.org', 'composer', 'https://repo.packagist.org')
        ];

        foreach ($config->repositories() as $name => $repository) {
            $sources[] = ExtensionSource::fromValues(
                $name,
                $repository['type'],
                $repository['url']
            );
        }

        return ExtensionSources::fromItems($sources);
    }

    public function findInstalledExtensions(): Extensions
    {
        return $this->transformPackagesToExtensions(
            $this->composer
                ->findInstalledPackages()
                ->filterByType($this->config->appExtensionsPackageType())
        );
    }

    public function findAvailableExtensions(): Extensions
    {
        return $this->transformPackagesToExtensions(
            $this->composer->findPackagesByType($this->config->appExtensionsPackageType())
        );
    }

    private function transformPackagesToExtensions(Packages $packages): Extensions
    {
        return Extensions::fromItems(array_map([$this, 'transformPackageToExtension'], iterator_to_array($packages)));
    }

    private function transformPackageToExtension(PackageInterface $package): Extension
    {
        return Extension::fromValues(
            $package->getName(),
            $package->getVersion(),
            $package->getExtra()[$this->config->appExtensionsExtensionClassConfigField()]
        );
    }
}