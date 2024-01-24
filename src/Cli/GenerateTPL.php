<?php

namespace MrEssex\CubexTranslate\Cli;

use MrEssex\CubexCli\ConsoleCommand;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Catalog\Message;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTPL extends ConsoleCommand
{
  private static array $replacements = [
    '(s)'  => 's',
    '(fe)' => 'ves',
    '(o)'  => 'oes',
  ];

  private OutputInterface $output;

  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    $this->output = $output;

    // iterate through all files in the source directory and nested directories
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_sourceDirectory()));

    foreach($files as $file)
    {
      if($file->isDir())
      {
        continue;
      }

      $this->_processFile($file);
    }
  }

  protected function _processFile($file)
  {
    $contents = file_get_contents($file->getPathname());
    $regex = [
      '/\$this->_\((?:\W?)*[\'"]([^\'"]*)[\'"],(?:[\W])*[\'"](.*)[\'"](?:,(?:[\W\w])*\])?(?:\W)*\)/' => '_processDefault',
      '/\$this->_t\((?:\W?)*[\'"](.*)[\'"](?:,(?:[\W\w])*\])?(?:\W)*\)/'                             => '_processMD5',
      '/\$this->_p\((?:\W?)*[\'"](.*)[\'"],(?:\W?)*(.*)[\'"],(?:\W\w)(?:\W\w?)*\)/'                  => '_processPlural',
      '/\$this->_sp\((?:\W?)*[\'"](.*)[\'"],(?:\W\w)(?:\W\w?)*\)/'                                   => '_processSimplePlural',

    ];

    foreach($regex as $r => $method)
    {
      $matches = [];
      preg_match_all($r, $contents, $matches);
      if(!empty($matches[0]))
      {
        $this->$method($matches, $file);
      }
    }
  }

  protected function _processDefault($matches, $file)
  {
    $matches = array_combine($matches[1], $matches[2]);
    $this->_processMatches($matches, $file);
  }

  protected function _processMD5($matches, $file)
  {
    $newMatches = [];
    foreach($matches[1] as $k => $v)
    {
      $newMatches[md5($v)] = $v;
    }

    $this->_processMatches($newMatches, $file);
  }

  protected function _processPlural($matches, $file)
  {
    $matches = array_combine($matches[1], $matches[2]);

    $newMatches = [];
    foreach($matches as $singular => $plural)
    {
      $newMatches[md5($singular . $plural)] = [
        1           => $singular,
        'n..0,2..n' => $plural,
      ];
    }

    $this->_processMatches($newMatches, $file);
  }

  protected function _processSimplePlural($matches, $file)
  {
    $newMatches = [];
    foreach($matches[1] as $simplePlural)
    {
      $singular = str_replace(array_keys(static::$replacements), '', $simplePlural);
      $plural = str_replace(array_keys(static::$replacements), array_values(static::$replacements), $simplePlural);

      $newMatches[md5($singular . $plural)] = [
        1           => $singular,
        'n..0,2..n' => $plural,
      ];
    }

    $this->_processMatches($newMatches, $file);
  }

  protected function _processMatches(array $matches, $file)
  {
    $this->output->writeln("Processing file: " . $file->getPathname());
    $this->output->writeln("Found " . count($matches) . " matches");

    // load the tpl file and
    $catalog = $this->getTranslationCatalog();

    foreach($matches as $msgid => $msgstr)
    {
      if($catalog->getMessage($msgid) !== null)
      {
        continue;
      }

      if(is_array($msgstr))
      {
        $this->output->writeln("Processing match: " . $msgid . " => " . $msgstr[1] . " | " . $msgstr['n..0,2..n']);
      }
      else
      {
        $this->output->writeln("Processing match: " . $msgid . " => " . $msgstr);
      }

      $catalog->addMessage($msgid, Message::fromDefault($msgstr)->getOptions());
    }

    $this->_writeCatalog($catalog);
  }

  protected function _writeCatalog($catalog)
  {
    $root = rtrim($this->getContext()->getProjectRoot(), DIRECTORY_SEPARATOR);
    $transDir = $root . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;
    $catFile = $transDir . '_tpl.php';

    if(!file_exists($catFile))
    {
      if(!mkdir($transDir) && !is_dir($transDir))
      {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $transDir));
      }
    }

    file_put_contents($catFile, $catalog->asPhpFile(true));
  }

  protected function getTranslationCatalog()
  {
    $root = rtrim($this->getContext()->getProjectRoot(), DIRECTORY_SEPARATOR);
    $transDir = $root . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;

    $catFile = $transDir . '_tpl.php';
    if(file_exists($catFile))
    {
      //Load the existing template
      $tplCatalog = DynamicArrayCatalog::fromFile($catFile);
    }
    else
    {
      $tplCatalog = new DynamicArrayCatalog([]);
    }

    return $tplCatalog;
  }

  protected function _sourceDirectory(): string
  {
    $root = rtrim($this->getContext()->getProjectRoot(), DIRECTORY_SEPARATOR);
    return $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
  }
}
