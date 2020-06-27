<?php

namespace Tkotosz\CliAppWrapper;

use Composer\Package\PackageInterface;
use Exception;
use Github\Client;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Technodelight\GitShell\Api as Git;
use Technodelight\ShellExec\Exec;
use Tkotosz\CliAppWrapperApi\Api\V1\Application;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\ApplicationCommandResult;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\ApplicationConfig;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\ApplicationDirectory;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationFactory;
use Tkotosz\CliAppWrapperApi\Api\V1\ApplicationManager as ApplicationManagerInterface;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\Extension;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\Extensions;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\ExtensionSource;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\ExtensionSources;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\FileName;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\RelativePath;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\WorkingDirectory;
use Tkotosz\CliAppWrapperApi\Api\V1\Model\WorkingMode;
use Tkotosz\ComposerWrapper\Composer;
use Tkotosz\ComposerWrapper\Composer\Packages;

class ApplicationManager implements ApplicationManagerInterface
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Client */
    private $github;

    /** @var Downloader */
    private $downloader;

    /** @var ApplicationConfig */
    private $config;

    /** @var WorkingMode */
    private $workingMode;

    /** @var WorkingDirectory|null */
    private $workingDir = null;

    public function __construct(
        Filesystem $filesystem,
        Client $github,
        Downloader $downloader,
        ApplicationConfig $config,
        WorkingMode $workingMode
    ) {
        $this->filesystem = $filesystem;
        $this->github = $github;
        $this->downloader = $downloader;
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
        foreach ($this->config->localWorkingDirectoryResolvers() as $resolver) {
            if ($resolver === 'cwd' && ($path = $this->findCurrentWorkingDirectory())) {
                return WorkingDirectory::fromString($path);
            }

            if ($resolver === 'git' && ($path = $this->findGitRootDirectory())) {
                return WorkingDirectory::fromString($path);
            }
        }

        throw new RuntimeException(
            sprintf(
                'Error: Failed to resolve working directory (resolvers: "%s")',
                implode('","', $this->config->localWorkingDirectoryResolvers())
            )
        );
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
        if ($this->config->allowsExtensions() === null) {
            return ApplicationCommandResult::failure();
        }

        $package = $this->composer()->findPackageByName($extensionPackage);

        if ($package === null || $package->getType() !== $this->config->appExtensionsPackageType()) {
            return ApplicationCommandResult::failure();
        }

        return ApplicationCommandResult::fromInt(
            $this->composer()->installPackage($extensionPackage, $extensionVersion)
        );
    }

    public function removeExtension(string $extensionPackage): ApplicationCommandResult
    {
        if ($this->config->allowsExtensions() === null) {
            return ApplicationCommandResult::failure(255);
        }

        return ApplicationCommandResult::fromInt(
            $this->composer()->removePackage($extensionPackage)
        );
    }

    public function findInstalledExtensions(): Extensions
    {
        if ($this->config->allowsExtensions() === null) {
            return Extensions::fromItems([]);
        }

        return $this->transformPackagesToExtensions(
            $this->composer()
                ->findInstalledPackages()
                ->filterByType($this->config->appExtensionsPackageType())
        );
    }

    public function findAvailableExtensions(): Extensions
    {
        if ($this->config->allowsExtensions() === null) {
            return Extensions::fromItems([]);
        }

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

    public function init(array $extensions = []): int
    {
        try {
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

            foreach ($extensions as $extension) {
                $result = $this->installExtension($extension);
                if ($result->isFailure()) {
                    $this->destroy();
                    return $result->toInt();
                }
            }

            $_SERVER['argv'] = []; // avoid leaking original input arguments to the app
            $result = $this->createApplication()->init();
            if ($result !== 0) {
                $this->destroy();
                return $result;
            }

            return 0;
        } catch (Exception $e) {
            $this->destroy();
            return 255;
        }
    }

    public function update(): ApplicationCommandResult
    {
        $io = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
        $mode = $this->getWorkingMode()->isGlobal() ? 'global' : '';
        $currentAppBin = $this->getWorkingDirectory()->pathToFile(FileName::fromString($this->config->appExecutableName()))->toString();
        $io->writeln('Updating Application...');


        $io->title('1. Collect list of installed extensions');
        $extensions = [];
        foreach ($this->findInstalledExtensions() as $extension) {
            $io->writeln(sprintf('Found "%s"', $extension->name()));
            $extensions[] = $extension->name();
        }

        $io->title('2. Download latest application version');
        $newAppBin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->config->appExecutableName();
        $release = $this->github->repo()->releases()->all($this->config->githubUser(), $this->config->githubRepository())[0] ?? null;
        if ($release === null || !isset($release['assets'][0]['browser_download_url'])) {
            $io->error('Could not find any installable application release.');
            return ApplicationCommandResult::failure();
        }
        $result = $this->downloader->downloadWithCurl(new ConsoleOutput(), $release['assets'][0]['browser_download_url'], $newAppBin);
        if ($result === false) {
            $io->error('Could not download latest application version, upgrade aborted.');
            return ApplicationCommandResult::failure();
        }
        chmod($newAppBin, 0755);
        $io->writeln(sprintf('Latest phar successfully saved to "%s"', $newAppBin));


        $io->title('3. Backup current application version');
        $backupDir = $this->getCurrentAppDirAbsolutePath() . '.bak';
        $result = rename($this->getCurrentAppDirAbsolutePath(), $backupDir);
        if ($result === false) {
            $io->error('Could not backup current application version, upgrade aborted.');
            unlink($newAppBin);
            return ApplicationCommandResult::failure();
        }
        $io->writeln(sprintf('Backup successfully created at "%s"', $backupDir));


        $io->title('4. Init new application version');
        system(
            sprintf(
                '%s %s %s',
                $newAppBin,
                implode(' ', [$mode, 'init']),
                implode(' ', array_map('escapeshellarg', $extensions))
            )
        );


        $io->title('5. Replace application');
        $result = rename($newAppBin, $currentAppBin);
        if ($result === false) {
            $io->error('Could not replace current application with the new version.');

            $io->title('6. Restore application from backup');
            system(sprintf('rm -rf %s', escapeshellarg($this->getCurrentAppDirAbsolutePath())));
            rename($backupDir, $this->getCurrentAppDirAbsolutePath());

            return ApplicationCommandResult::failure();
        }
        $io->writeln('Application successfully replaced with the new version.');


        $io->title('6. Remove backup');
        system(sprintf('rm -rf %s', escapeshellarg($backupDir)));
        $io->writeln(sprintf('Backup successfully removed from "%s"', $backupDir));

        $io->success('Application successfully updated.');

        return ApplicationCommandResult::success();
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

    private function getCurrentAppDirAbsolutePath(): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                $this->getWorkingDirectory(),
                $this->config->appDir()
            ]
        );
    }

    private function findGitRootDirectory(): ?string
    {
        $git = new Git(new Exec('/usr/bin/env git'));

        try {
            return $git->topLevelDirectory() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function findCurrentWorkingDirectory(): ?string
    {
        return getcwd() ?: null;
    }
}