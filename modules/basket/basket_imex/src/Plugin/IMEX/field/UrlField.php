<?php

namespace Drupal\basket_imex\Plugin\IMEX\field;

use Drupal\basket_imex\Plugins\IMEXfield\BasketIMEXfieldInterface;

/**
 * UrlField IMEX type.
 *
 * @BasketIMEXfield(
 *   id = "url",
 *   type = {"basket_imex_old_redirect"},
 *   name = "Url",
 * )
 */
class UrlField implements BasketIMEXfieldInterface {

  /**
   * Getting data for export.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $fieldName
   *   Field that has been updated.
   */
  public function getValues($entity, $fieldName) {}

  /**
   * Data array formation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function setValues($entity, $importValue = '') {}

  /**
   * Additional field processing after $entity update / creation.
   *
   * @param object $entity
   *   Entity that has been updated.
   * @param string $importValue
   *   Import value.
   */
  public function postSave($entity, $importValue = '') {
    if (!empty($entity->basketIMEXupdateField) && $entity->basketIMEXupdateField == 'basket_imex_old_redirect') {
      \Drupal::database()->delete('basket_imex_redirect')
        ->condition('nid', $entity->id())
        ->execute();
      if (!empty($importValue)) {
        $url = parse_url($importValue);

        if (!empty($url['scheme'])) {
          unset($url['scheme']);
        }
        if (!empty($url['host'])) {
          unset($url['host']);
        }

        \Drupal::database()->insert('basket_imex_redirect')
          ->fields([
            'old_url' => $this->unparseUrl($url),
            'nid' => $entity->id(),
          ])
          ->execute();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unparseUrl($parsed_url) {
    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass = ($user || $pass) ? "$pass@" : '';
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
  }

}
