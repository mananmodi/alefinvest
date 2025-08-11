<?php

namespace Drupal\gtext\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Url;
use Drupal\gtext\GoogleTranslate;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringDatabaseStorage;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a APIController class for manage translation.
 */
class APIController extends ControllerBase {

  /**
   * Defines a class to store localized strings in the database.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $localeStorage;

  /**
   * Database API class.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Provides helpers to operate on files and stream wrappers.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new APIController.
   *
   * {@inheritDoc}
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    StringDatabaseStorage $localeStorage,
    ModuleHandler $moduleHandler,
    Connection $database,
    Messenger $messenger,
    FileSystem $fileSystem
  ) {
    $this->languageManager = $languageManager;
    $this->localeStorage = $localeStorage;
    $this->moduleHandler = $moduleHandler;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('locale.storage'),
      $container->get('module_handler'),
      $container->get('database'),
      $container->get('messenger'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTranslate($lid) {
    $this->languageManager->reset();

    $response = new AjaxResponse();

    $this->database->delete('locales_target')->condition('lid', $lid)->execute();
    $this->database->delete('locales_source')->condition('lid', $lid)->execute();

    $languages = $this->languageManager->getLanguages();
    _gtext_refresh_locale_translations(array_keys($languages), [$lid]);
    _gtext_refresh_locale_configuration(array_keys($languages), [$lid]);

    $response->addCommand(new InvokeCommand('body', 'append', ['<script>location.reload();</script>']));

    $this->languageManager->reset();
    return $response;
  }

  /**
   * Provide method to request translate string.
   *
   * {@inheritDoc}
   */
  public function googleTranslateText(Request $request) {
    if (!$request->request->has('target') || !$request->request->has('lang') || !$request->request->has('text') || $request->getMethod() != Request::METHOD_POST) {
      throw new BadRequestHttpException();
    }

    $target = $request->request->get('target');
    $lang   = $request->request->get('lang');
    $text   = $request->request->get('text');
    $source = 'auto';
    if ($request->request->has('source')) {
      $source = $request->request->get('source');
    }

    $response = new AjaxResponse();
    if (!empty($target) && !empty($lang) && !empty($text)) {
      $translate = GoogleTranslate::translate($source, $lang, $text);

      if (!empty($translate)) {
        $translate = str_replace(trim($text), $translate, $text);
        $response->addCommand(new InvokeCommand('#' . $target, 'val', [$translate]));
      }
      $response->addCommand(new InvokeCommand('#' . $target, 'removeClass', ['gtext-in-process']));
      $response->addCommand(new InvokeCommand('#' . $target, 'removeClass', ['gtext-in-process']));
    }
    $response->addCommand(new AppendCommand('body', ['#type' => 'status_messages']));
    return $response;
  }

  /**
   * Provide method to export translate string.
   *
   * {@inheritDoc}
   */
  public function translateExport(Request $request, $langcode, $group) {
    $host = 'project';
    if ($request->server->has('HTTP_HOST')) {
      $host = str_replace('www.', '', $request->server->get('HTTP_HOST'));
    }
    elseif ($request->server->has('SERVER_NAME')) {
      $host = str_replace('www.', '', $request->server->get('SERVER_NAME'));
    }

    $filename = $host . '-' . ($group == '_all' ? 'all' : $group) . '-' . $langcode . '-' . date('Y-m-d_His') . '.po';

    $strings = $this->getStrings($langcode, $group);
    if (!empty($strings)) {
      $uri = $this->fileSystem->getTempDirectory() . '/' . $filename;

      $header = new PoHeader($langcode);
      $header->setProjectName($host . '-' . ($group == '_all' ? 'all' : $group));
      $header->setLanguageName($langcode);
      $header->setFromString('Plural-Forms: ' . $this->getPluralForm($langcode));

      $writer = new PoStreamWriter();
      $writer->setHeader($header);
      $writer->setURI($uri);
      $writer->open();
      foreach ($strings as $string) {
        $text = (array) $string;
        if (empty($string->translation)) {
          continue;
        }
        $item = new PoItem();
        $item->setFromArray($text);
        $writer->writeItem($item);
      }
      $writer->close();

      $response = new BinaryFileResponse($uri, 200, [], FALSE, 'attachment');
      $response->deleteFileAfterSend(TRUE);
      return $response;
    }
    else {
      $this->messenger->addMessage(t('Not found strings for export'));
      return new RedirectResponse(Url::fromRoute('gtext.translate')->toString());
    }
  }

  /**
   * Provide method to return string.
   *
   * {@inheritDoc}
   */
  protected function getStrings($langcode, $context = '_all') {
    $conditions = [
      'language' => $langcode,
    ];
    $options    = [
      'translated'     => TRUE,
    ];

    if ($context != '_none') {
      if (empty($context) || $context == '_all') {
        $contexts = array_keys($this->moduleHandler->invokeAll('gtext_contexts'));
        if (empty($contexts)) {
          $contexts[] = 'not_exists';
        }
        $conditions['context'] = $contexts;
      }
      else {
        $options['filters']['context'] = $context;
      }
    }

    return $this->localeStorage->getTranslations($conditions, $options);
  }

  /**
   * Provide plural forms for language.
   */
  protected function getPluralForm($langcode) {
    switch ($langcode) {
      case 'am':
      case 'ca':
      case 'fil':
      case 'fr':
      case 'hy':
      case 'mg':
      case 'oc':
      case 'zh-hant':
        return 'nplurals=2; plural=(n>1);';

      case 'ar':
        return 'nplurals=6; plural=((n==1)?(0):((n==0)?(1):((n==2)?(2):((((n%100)>=3)&&((n%100)<=10))?(3):((((n%100)>=11)&&((n%100)<=99))?(4):5)))));';

      case 'be':
      case 'bs':
      case 'cs':
      case 'hr':
      case 'ru':
      case 'sr':
      case 'uk':
        return 'nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));';

      case 'bo':
      case 'kk':
      case 'ky':
      case 'lo':
      case 'tr':
      case 'ug':
      case 'vi':
        return 'nplurals=1; plural=0;';

      case 'cy':
        return 'nplurals=4; plural=((n==1)?(0):((n==2)?(1):(((n!=8)&&(n!=11))?(2):3)));';

      case 'ga':
        return 'nplurals=5; plural=((n==1)?(0):((n==2)?(1):((n<7)?(2):((n<11)?(3):4))));';

      case 'gd':
        return 'nplurals=4; plural=(((n==1)||(n==11))?(0):(((n==2)||(n==12))?(1):(((n>2)&&(n<20))?(2):3)));';

      case 'jv':
        return 'nplurals=2; plural=(n!=0);';

      case 'lt':
        return 'nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):((((n%10)>=2)&&(((n%100)<10)||((n%100)>=20)))?(1):2));';

      case 'lv':
        return 'nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):((n!=0)?(1):2));';

      case 'mk':
        return 'nplurals=2; plural=(((n==1)||((n%10)==1))?(0):1);';

      case 'pl':
        return 'nplurals=3; plural=((n==1)?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));';

      case 'ro':
        return 'nplurals=3; plural=((n==1)?(0):(((n==0)||(((n%100)>0)&&((n%100)<20)))?(1):2));';

      case 'sk':
        return 'nplurals=3; plural=((n==1)?(0):(((n>=2)&&(n<=4))?(1):2));';

      case 'sl':
        return 'nplurals=4; plural=(((n%100)==1)?(0):(((n%100)==2)?(1):((((n%100)==3)||((n%100)==4))?(2):3)));';

    }

    return 'nplurals=2; plural=(n!=1);';
  }

}
