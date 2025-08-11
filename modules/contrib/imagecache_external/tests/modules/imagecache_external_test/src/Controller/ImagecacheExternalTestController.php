<?php

namespace Drupal\imagecache_external_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class ImagecacheExternalTestController extends ControllerBase {

  /**
   * Return the contents of an image.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function api() {
    $content =     file_get_contents(\Drupal::service('extension.list.module')->getPath('imagecache_external') . '/tests/assets/el-blue.png');
    $response = new Response($content);
    $response->headers->set('Content-Type', 'image/png');
    return $response;
  }

}
