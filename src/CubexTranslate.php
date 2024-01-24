<?php
namespace MrEssex\CubexTranslate;

use Google\Cloud\Translate\V2\TranslateClient;
use Packaged\Context\Context;
use Packaged\Rwd\Language\LanguageCode;
use Statickidz\GoogleTranslate;

class CubexTranslate
{
  protected static null|TranslateClient|GoogleTranslate $_client = null;

  public static array $common = [
    LanguageCode::CODE_EN,
    LanguageCode::CODE_DA,
    LanguageCode::CODE_PL,
    LanguageCode::CODE_TR,
    LanguageCode::CODE_SV,
    LanguageCode::CODE_PT,
    LanguageCode::CODE_NO,
    LanguageCode::CODE_NL,
    LanguageCode::CODE_ES,
    LanguageCode::CODE_DE,
    LanguageCode::CODE_FR,
    LanguageCode::CODE_IT,
  ];

  public static function translate(Context $ctx, $text, $languageCode): string
  {
    if(empty($text) || $languageCode === LanguageCode::CODE_EN)
    {
      return $text;
    }

    $client = static::getClient($ctx);

    $src = preg_replace_callback(
      ['/\{(\w+)\}/', '/\*/',],
      static function ($match) { return '<span class="notranslate">' . $match[0] . '</span>'; },
      $text
    );

    if($client instanceof GoogleTranslate)
    {
      $text = $client::translate('en', $languageCode, $src);
    }
    else if($client instanceof TranslateClient)
    {
      $text = $client->translate($src, ['source' => 'en', 'target' => $languageCode]);
      if(!empty($trans['text']))
      {
        return htmlspecialchars_decode(
          str_replace(['<span class="notranslate">', '</span>'], '', $trans['text']),
          ENT_QUOTES | ENT_HTML5
        );
      }
    }

    return $text;
  }

  public static function getClient(Context $ctx): GoogleTranslate|TranslateClient
  {
    if(!static::$_client)
    {
      $conf = $ctx->config()->getSection('i18n', false);
      if($conf)
      {
        $key = $conf->has('google_translate_key');

        if($key)
        {
          return static::$_client = new TranslateClient(['key' => $key]);
        }
      }

      return static::$_client = new GoogleTranslate();
    }

    return static::$_client;
  }
}
