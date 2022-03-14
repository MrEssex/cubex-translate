<?php

namespace MrEssex\CubexTranslate\Cli;

use Cubex\Console\ConsoleCommand;
use MrEssex\CubexTranslate\CubexTranslate;
use Packaged\Helpers\ValueAs;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Catalog\Message;
use Packaged\I18n\Tools\Gettext\PoFile;
use Packaged\I18n\Tools\Gettext\PoTranslation;
use Packaged\Rwd\Language\LanguageCode;

class Translate extends ConsoleCommand
{
  /** @short l */
  public $lang;
  /** @flag */
  public $common;

  public function process(): void
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
      $poEdit = file_exists($poLoc) ? PoFile::fromString(file_get_contents($poLoc)) : new PoFile($lang);
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

    $tran->setAdditionalPluralTranslations($options);
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
    // Assuming this will be run from vendor
    return dirname(__DIR__, 5) . '/translations/';
  }
}
