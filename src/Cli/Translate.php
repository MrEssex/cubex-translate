<?php

namespace MrEssex\CubexTranslate\Cli;

use MrEssex\CubexCli\ConsoleCommand;
use MrEssex\CubexTranslate\CubexTranslate;
use MrEssex\CubexTranslate\PluralForms;
use Packaged\Helpers\ValueAs;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Catalog\Message;
use Packaged\I18n\Tools\Gettext\PoFile;
use Packaged\I18n\Tools\Gettext\PoTranslation;
use Packaged\Rwd\Language\LanguageCode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Translate extends ConsoleCommand
{
  /** @short l */
  public $lang;
  /** @flag */
  public $common;

  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    if(!$this->lang && $this->common)
    {
      $this->lang = CubexTranslate::$common;
    }

    $this->_iterateLanguages($this->_translationsDir());
  }

  protected function _iterateLanguages(string $location): void
  {
    foreach(ValueAs::arr($this->lang) as $lang)
    {
      $poLoc = $location . $lang . '.po';

      $poEdit = file_exists($poLoc) ?
        PoFile::fromString(file_get_contents($poLoc)) :
        new PoFile($lang, PluralForms::getPluralForm($lang));

      $template = DynamicArrayCatalog::fromFile($location . '_tpl.php');

      foreach($template->getData() as $mid => $options)
      {
        if($poEdit && $poEdit->getTranslation($mid) instanceof PoTranslation)
        {
          //Trust the existing po translation is correct
          continue;
        }

        $this->_createTranslations($mid, $options, $lang, $poEdit);
      }

      $this->_createPoFile($poLoc, (string)$poEdit);

      echo PHP_EOL;
    }
  }

  protected function _createTranslations($mid, $options, $lang, PoFile $poEdit): void
  {
    $tran = new PoTranslation($mid);
    $tran->setReferences([$mid]);
    $tran->setSingularSource($mid);

    //New translation, needs work
    $tran->setNeedsWork(true);

    if(isset($options[Message::DEFAULT_OPTION]) && !empty($options[Message::DEFAULT_OPTION]))
    {
      $tran->setComments(explode(PHP_EOL, $options[Message::DEFAULT_OPTION]));
      $tran->setSingularSource($this->_getTranslation($options[Message::DEFAULT_OPTION], LanguageCode::CODE_EN));
      $tran->setSingularTranslation($this->_getTranslation($options[Message::DEFAULT_OPTION], $lang));
      unset($options[Message::DEFAULT_OPTION]);
    }

    if(isset($options['n']))
    {
      $tran->setPluralSource($mid . '__plural');
      $tran->setPluralTranslation($options['n']);
      unset($options['n']);
    }

    if(isset($options['n..0,2..n']))
    {
      $tran->setSingularSource($options[1]);
      $tran->setPluralSource($options['n..0,2..n']);

      $tran->setSingularTranslation($this->_getTranslation($options[1], $lang));
      $tran->setPluralTranslation($this->_getTranslation($options['n..0,2..n'], $lang));
      unset($options['n..0,2..n']);
    }

    $poEdit->addTranslation($tran);
  }

  protected function _getTranslation($text, $lang): string
  {
    return CubexTranslate::translate($this->getContext(), $text, $lang);
  }

  protected function _createPoFile(string $location, string $contents): void
  {
    file_put_contents($location, $contents);
  }

  protected function _translationsDir(): string
  {
    $root = rtrim($this->getContext()->getProjectRoot(), DIRECTORY_SEPARATOR);
    return $root . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;
  }
}
