<?php

namespace MrEssex\CubexTranslate;

use Packaged\Rwd\Language\LanguageCode;

class PluralForms
{
  public static function getPluralForm(string $languageCode)
  {
    // Support our basic languages
    return match ($languageCode)
    {
      LanguageCode::CODE_FR, LanguageCode::CODE_TR => "nplurals=2; plural=(n > 1);",
      LanguageCode::CODE_PL => "nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);",
      default => "nplurals=2; plural=(n != 1);",
    };
  }
}
