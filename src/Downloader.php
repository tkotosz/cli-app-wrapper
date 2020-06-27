<?php

namespace Tkotosz\CliAppWrapper;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Downloader
{
    /** @var ProgressBar */
    private $progressBar;

    public function downloadWithCurl(OutputInterface $output, $downloadUrl, $targetFile)
    {
        $this->progressBar = $this->createProgressBar($output);

        $f = fopen($targetFile, 'w');
        $ch = $this->initCurl($downloadUrl, $f, [$this, 'progress']);

        curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        fclose($f);

        $this->progressBar->finish();
        $output->writeln('');
        $this->progressBar = null;

        return $err == 0;
    }

    private function progress()
    {
        $args = func_get_args();

        if (is_resource($args[0])) {
            $downloadTotal = $args[1];
            $downloadedNow = $args[2];
        } else {
            $downloadTotal = $args[0];
            $downloadedNow = $args[1];
        }

        if ($downloadTotal > 0) {
            $progressPercentage = (int) ($downloadedNow / $downloadTotal * 100);
            if ($progressPercentage > 0 && $progressPercentage < 100) {
                $this->progressBar->setProgress($progressPercentage);
            }
        }
    }

    private function createProgressBar(OutputInterface $output): ProgressBar
    {
        $progress = new ProgressBar($output);
        $progress->setFormat('%bar% %percent%%');
        $progress->setBarCharacter('<bg=green> </>');
        $progress->setEmptyBarCharacter('<bg=white> </>');
        $progress->setProgressCharacter('<bg=green> </>');
        $progress->setBarWidth(50);
        $progress->setRedrawFrequency(1);
        $progress->setMaxSteps(100);

        return $progress;
    }

    private function initCurl($downloadUrl, $f, $callback)
    {
        $ch = curl_init($downloadUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, $callback);

        return $ch;
    }
}