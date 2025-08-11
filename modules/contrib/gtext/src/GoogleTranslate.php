<?php

namespace Drupal\gtext;

use Drupal\Core\Url;
use Google\Cloud\Translate\V2\TranslateClient;

/**
 * Provides a GoogleTranslate class.
 */
class GoogleTranslate {

  /**
   * Method to translate string.
   */
  public static function translate($source, $target, $text) {
    $translation = FALSE;

    $apiKey = \Drupal::config('gtext.settings')->get('google_api_key', FALSE);
    if (!empty($apiKey)) {
      try {
        $client = new TranslateClient(
          [
            'key' => $apiKey,
          ]
        );
        $response = $client->translate(
          $text, [
            'target' => $target,
          ]
        );
        if (!empty($response['text'])) {
          $translation = $response['text'];
        }
      }
      catch (\Exception $e) {
        $message = @json_decode($e->getMessage(), TRUE);
        if ($message !== FALSE && isset($message['error']['code']) && isset($message['error']['message'])) {
          $message = '[' . $message['error']['code'] . '] ' . $message['error']['message'];
        }
        else {
          $message = $e->getMessage();
        }
        \Drupal::messenger('gtext')->addWarning($message);
      }
    }
    if ($translation === FALSE) {
      try {
        $response    = self::requestTranslation($source, $target, $text);
        $translation = self::getSentencesFromJson($response);
      }
      catch (\Exception $e) {
        \Drupal::messenger('gtext')->addWarning($e->getMessage());
        return FALSE;
      }
    }
    return $translation;
  }

  /**
   * Method to request translation.
   */
  protected static function requestTranslation($source, $target, $text) {
    if (mb_strlen($text) >= 1000) {
      throw new \Exception(
        t(
          'Maximum number of characters exceeded: 1000. To increase the limits, <a href="@url">enter the Google Translate key on the page</a>',
          [
            '@url' => Url::fromRoute('gtext.translate.settings_page')->toString(),
          ]
        ) . ''
      );
    }

    $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
    $fields = [
      'sl' => urlencode($source),
      'tl' => urlencode($target),
      'q'  => urlencode($text),
    ];
    $fields_string = "";
    foreach ($fields as $key => $value) {
      $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }

  /**
   * Parse json response.
   */
  protected static function getSentencesFromJson($json) {
    $sentencesArray = json_decode($json, TRUE);
    $sentences = "";
    foreach ($sentencesArray["sentences"] as $s) {
      $sentences .= isset($s["trans"]) ? $s["trans"] : '';
    }
    return $sentences;
  }

}
