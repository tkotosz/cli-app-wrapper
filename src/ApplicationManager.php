<?php

namespace Tkotosz\CliAppWrapper;

use Composer\Package\PackageInterface;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Tkotosz\CliAppWrapperApi\Application;
use Tkotosz\CliAppWrapperApi\ApplicationCommandResult;
use Tkotosz\CliAppWrapperApi\ApplicationConfig;
use Tkotosz\CliAppWrapperApi\ApplicationDirectory;
use Tkotosz\CliAppWrapperApi\ApplicationFactory;
use Tkotosz\CliAppWrapperApi\ApplicationManager as ApplicationManagerInterface;
use Tkotosz\CliAppWrapperApi\Extension;
use Tkotosz\CliAppWrapperApi\Extensions;
use Tkotosz\CliAppWrapperApi\ExtensionSource;
use Tkotosz\CliAppWrapperApi\ExtensionSources;
use Tkotosz\CliAppWrapperApi\RelativePath;
use Tkotosz\CliAppWrapperApi\WorkingDirectory;
use Tkotosz\CliAppWrapperApi\WorkingMode;
use Tkotosz\ComposerWrapper\Composer;
use Tkotosz\ComposerWrapper\Composer\Packages;

class ApplicationManager implements ApplicationManagerInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var ApplicationConfig */
    private $config;

    /** @var WorkingMode */
    private $workingMode;

    /** @var WorkingDirectory|null */
    private $workingDir = null;

    public function __construct(Filesystem $filesystem, ApplicationConfig $config, WorkingMode $workingMode)
    {
        $this->filesystem = $filesystem;
        $this->config = $config;
        $this->workingMode = $workingMode;
    }

    public function getApplicationConfig(): ApplicationConfig
    {
        return $this->config;
    }

    public function getWorkingMode(): WorkingMode
    {
        return $this->workingMode;
    }

    public function getWorkingDirectory(): WorkingDirectory
    {
        if ($this->workingDir === null) {
            $this->workingDir = WorkingDirectory::fromString(
                $this->workingMode->isGlobal() ? $this->getGlobalWorkingDirectory() : $this->getLocalWorkingDirectory()
            );
        }

        return $this->workingDir;
    }

    public function getLocalWorkingDirectory(): WorkingDirectory
    {
        // TODO Allow to use git working dir as local working dir instead of cwd
        return WorkingDirectory::fromString(getcwd());
    }

    public function getGlobalWorkingDirectory(): WorkingDirectory
    {
        if ($path = getenv('XDG_CONFIG_HOME')) {
            return WorkingDirectory::fromString($path);
        }

        if ($path = getenv('HOME')) {
            return WorkingDirectory::fromString($path);
        }

        return WorkingDirectory::fromString(
            getenv('HOMEDRIVE') . DIRECTORY_SEPARATOR . getenv('HOMEPATH')
        );
    }

    public function getApplicationDirectory(): ApplicationDirectory
    {
        return ApplicationDirectory::fromAbsolutePath(
            $this->getWorkingDirectory()->pathTo(RelativePath::fromString($this->config->appDir()))
        );
    }

    public function getLocalApplicationDirectory(): ApplicationDirectory
    {
        return ApplicationDirectory::fromAbsolutePath(
            $this->getLocalWorkingDirectory()->pathTo(RelativePath::fromString($this->config->appDir()))
        );
    }

    public function getGlobalApplicationDirectory(): ApplicationDirectory
    {
        return ApplicationDirectory::fromAbsolutePath(
            $this->getGlobalWorkingDirectory()->pathTo(RelativePath::fromString($this->config->appDir()))
        );
    }

    public function installExtension(string $extensionPackage, string $extensionVersion = null): ApplicationCommandResult
    {
        return ApplicationCommandResult::fromInt(
            $this->composer()->installPackage($extensionPackage, $extensionVersion)
        );
    }

    public function removeExtension(string $extensionPackage): ApplicationCommandResult
    {
        return ApplicationCommandResult::fromInt(
            $this->composer()->removePackage($extensionPackage)
        );
    }

    public function findInstalledExtensions(): Extensions
    {
        return $this->transformPackagesToExtensions(
            $this->composer()
                ->findInstalledPackages()
                ->filterByType($this->config->appExtensionsPackageType())
        );
    }

    public function findAvailableExtensions(): Extensions
    {
        return $this->transformPackagesToExtensions(
            $this->composer()->findPackagesByType($this->config->appExtensionsPackageType())
        );
    }

    public function addExtensionSource(ExtensionSource $extensionSource): ApplicationCommandResult
    {
        $config = $this->composer()->getComposerConfig();

        return ApplicationCommandResult::fromInt(
            $this->composer()->changeComposerConfig(
                $config->addRepository($extensionSource->name(), $extensionSource->type(), $extensionSource->url())
            )
        );
    }

    public function removeExtensionSource(string $name): ApplicationCommandResult
    {
        $config = $this->composer()->getComposerConfig();

        return ApplicationCommandResult::fromInt(
            $this->composer()->changeComposerConfig(
                $config->removeRepository($name)
            )
        );
    }

    public function findExtensionSources(): ExtensionSources
    {
        $config = $this->composer()->getComposerConfig();

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

    public function init(): int
    {
        $result = $this->composer()->init($this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $this->config->appDir());
        if ($result !== 0) {
            $this->destroy();
            return $result;
        }

        $config = $this->composer()->getComposerConfig();
        foreach ($this->config->repositories() as $key => $repository) {
            $config = $config->addRepository($key, $repository['type'], $repository['url']);
        }
        $config = $config->addProvide('tkotosz/cli-app-wrapper-api', '*');

        $result = $this->composer()->changeComposerConfig($config);
        if ($result !== 0) {
            $this->destroy();
            return $result;
        }

        $result = $this->composer()->installPackage($this->config->appPackage(), $this->config->appVersion());
        if ($result !== 0) {
            $this->destroy();
            return $result;
        }

        $result = $this->createApplication()->init();
        if ($result !== 0) {
            $this->destroy();
            return $result;
        }

        return 0;
    }

    public function createApplication(): Application
    {
        if ($this->autoloadWrappedApplication()) {
            $appFactory = $this->config->appFactory();

            if (!class_exists($appFactory)) {
                throw new RuntimeException(
                    sprintf('Application Factory class "%s" not found', $appFactory)
                );
            }

            if (!is_subclass_of($appFactory, ApplicationFactory::class)) {
                throw new RuntimeException(
                    sprintf('Application Factory "%s" must implement "%s"', $appFactory, ApplicationFactory::class)
                );
            }

            return $appFactory::create($this);
        }

        return new AppInitApplication($this);
    }

    private function autoloadWrappedApplication(): bool
    {
        $autoload = implode(
            DIRECTORY_SEPARATOR,
            [
                $this->getWorkingDirectory(),
                $this->config->appDir(),
                'autoload.php'
            ]
        );

        if (!file_exists($autoload)) {
            return false;
        }

        require $autoload;

        return true;
    }

    private function destroy(): void
    {
        $this->filesystem->remove($this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $this->config->appDir());
    }

    private function composer(): Composer
    {
        return new Composer(
            $this->filesystem,
            new ArgvInput(),
            new ConsoleOutput(),
            implode(DIRECTORY_SEPARATOR, [$this->getWorkingDirectory(), $this->config->appDir(), 'composer.json'])
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