<?php
namespace MrEssex\CubexTranslate;

use Packaged\Context\Context;
use Packaged\Rwd\Language\LanguageCode;
use Statickidz\GoogleTranslate;

class CubexTranslate
{
  /** @var GoogleTranslate */
  protected static $_client;

  public static array $common = [
    LanguageCode::CODE_EN,
    LanguageCode::CODE_ES,
    LanguageCode::CODE_FR,
  ];

  public static function translate(Context $ctx, $text, $languageCode): string
  {
    if(empty($text) || $languageCode === LanguageCode::CODE_EN)
    {
      return $text;
    }

    if(static::$_client === null)
    {
      static::$_client = new GoogleTranslate();
    }

    if(static::$_client instanceof GoogleTranslate)
    {
      $text = static::$_client::Translate('en', $languageCode, $text);
    }

    return $text;
  }
}
