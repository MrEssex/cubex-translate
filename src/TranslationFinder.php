<?php

namespace MrEssex\CubexTranslate;

use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\Context\WithContext;
use Packaged\Context\WithContextTrait;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Catalog\Message;
use Permafrost\PhpCodeSearch\Searcher;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationFinder implements ContextAware, WithContext
{
  use ContextAwareTrait;
  use WithContextTrait;

  private static array $replacements = [
    '(s)'  => 's',
    '(fe)' => 'ves',
    '(o)'  => 'oes',
  ];

  public function __construct(protected OutputInterface $output) { }

  public function processFile(string $contents): void
  {
    $searcher = new Searcher();
    $searcher->methods(['_', '_t', '_p', '_sp']);

    $searchResults = $searcher->searchCode($contents);

    if(!$searchResults->results || $searchResults->hasErrors())
    {
      return;
    }

    foreach($searchResults->results as $result)
    {
      switch($result->node->methodName)
      {
        case '_':
          $this->_processDefault($result->node->args);
          break;
        case '_t':
          $this->_processMD5($result->node->args);
          break;
        case '_p':
          $this->_processPlural($result->node->args);
          break;
        case '_sp':
          $this->_processSimplePlural($result->node->args);
          break;
      }
    }
  }

  protected function _processDefault(array $matches): void
  {
    if(count($matches) < 2)
    {
      return;
    }

    $messageId = $matches[0]->value;
    $default = $matches[1]->value;
    $replacements = array_key_exists(2, $matches) ? $matches[2]->value : null;
    $choice = array_key_exists(3, $matches) ? $matches[3]->value : null;

    $this->_processMatches([$messageId => $default]);
  }

  protected function _processMD5($matches): void
  {
    if(count($matches) < 1)
    {
      return;
    }

    $default = $matches[0]->value;
    $replacements = array_key_exists(1, $matches) ? $matches[1]->value : null;
    $choice = array_key_exists(2, $matches) ? $matches[2]->value : null;

    $this->_processMatches([md5($default) => $default]);
  }

  protected function _processPlural($matches)
  {
    if(count($matches) < 1)
    {
      return;
    }

    $singular = $matches[0]->value;
    $plural = $matches[1]->value;
    $n = array_key_exists(2, $matches) ? $matches[2]->value : null;
    $replacements = array_key_exists(3, $matches) ? $matches[3]->value : null;

    $this->_processMatches([md5($singular . $plural) => [1 => $singular, 'n..0,2..n' => $plural]]);
  }

  protected function _processSimplePlural($matches)
  {
    if(count($matches) < 1)
    {
      return;
    }

    $singular = str_replace(array_keys(static::$replacements), '', $matches[0]->value);
    $plural = str_replace(array_keys(static::$replacements), array_values(static::$replacements), $matches[0]->value);
    $n = array_key_exists(1, $matches) ? $matches[1]->value : null;
    $replacements = array_key_exists(2, $matches) ? $matches[2]->value : null;

    $this->_processMatches([md5($singular . $plural) => [1 => $singular, 'n..0,2..n' => $plural]]);
  }

  protected function _processMatches(array $matches)
  {
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

  protected function _writeCatalog(DynamicArrayCatalog $catalog)
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
}
