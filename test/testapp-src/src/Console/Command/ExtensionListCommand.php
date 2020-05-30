<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;

class ExtensionListCommand extends Command
{
    /** @var ApplicationManager */
    private $applicationManager;

    public function __construct(ApplicationManager $applicationManager)
    {
        $this->applicationManager = $applicationManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('extension:list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $availableExtensions = $this->applicationManager->findAvailableExtensions();
        $installedExtensions = $this->applicationManager->findInstalledExtensions();

        $installedExtensionVersions = [];
        foreach ($installedExtensions as $extension) {
            $installedExtensionVersions[$extension->name()] = $extension->version();
        }

        $list = [];
        foreach ($availableExtensions as $extension) {
            $list[] = [
                'Name' => $extension->name(),
                'Installed' => isset($installedExtensionVersions[$extension->name()]) ? 'Yes' : 'No',
                'Latest Version' => ($extension->version() === '9999999-dev') ? 'dev-master' : $extension->version(),
                'Installed Version' => isset($installedExtensionVersions[$extension->name()]) ? $installedExtensionVersions[$extension->name()] : 'None'
            ];
        }

        $table = new Table($output);
        if (count($list) > 0) {
            $table->addRows($list);
            $table->setHeaders(array_keys(array_shift($list)));
            $table->render();
        }

        return 0;
    }
}