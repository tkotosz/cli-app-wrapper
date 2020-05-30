<?php

namespace Tkotosz\TestApp\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkotosz\CliAppWrapperApi\ApplicationManager;
use Tkotosz\CliAppWrapperApi\ExtensionSource;

class ExtensionSourceListCommand extends Command
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
        $this
            ->setName('extension:source:list')
            ->setDescription('List Extension Sources')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = [];
        foreach ($this->applicationManager->findExtensionSources() as $source) {
            $list[] = [
                'Name' => $source->name(),
                'Type' => $source->type(),
                'URL' => $source->url()
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
