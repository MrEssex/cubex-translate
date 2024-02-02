<?php

namespace MrEssex\CubexTranslate\Cli;

use MrEssex\CubexCli\ConsoleCommand;
use MrEssex\CubexTranslate\TranslationFinder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindTranslations extends ConsoleCommand
{
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    // iterate through all files in the source directory and nested directories
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_sourceDirectory()));

    foreach($files as $file)
    {
      if($file->isDir() || !in_array($file->getExtension(), ['php', 'phtml']))
      {
        continue;
      }

      $finder = TranslationFinder::withContext($this, $output);
      $finder->processFile(file_get_contents($file->getPathname()));
    }
  }

  protected function _sourceDirectory(): string
  {
    $root = rtrim($this->getContext()->getProjectRoot(), DIRECTORY_SEPARATOR);
    return $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
  }
}
