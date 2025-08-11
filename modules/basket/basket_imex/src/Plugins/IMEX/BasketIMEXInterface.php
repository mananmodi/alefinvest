<?php

namespace Drupal\basket_imex\Plugins\IMEX;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class of BasketIMEXInterface.
 */
interface BasketIMEXInterface {

  /**
   * Whether the field matching service is available to the plugin.
   */
  public function getImportExportSettings();

  /**
   * List of fields that will participate in the settings.
   */
  public function getSettingsFields();

  /**
   * Entity search.
   *
   * @param array $updateInfo
   *   Update data.
   */
  public function getEntity(array $updateInfo);

  /**
   * Call after updating / creating entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   * @param array $updateInfo
   *   Update data.
   */
  public function postSave(EntityInterface $entity, array $updateInfo);

  /**
   * Import fields.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getImportFormFields(array &$form, FormStateInterface $form_state);

  /**
   * Get the list to run for import.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getImportRows(FormStateInterface $form_state);

  /**
   * Import Handler Callback.
   *
   * @param array $info
   *   Info array.
   * @param array|null $context
   *   Context array.
   */
  public function processImport(array $info, ?array &$context);

  /**
   * Export fields.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getExportFormFields(array &$form, FormStateInterface $form_state);

  /**
   * Getting the list to run for export.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   */
  public function getExportRows(FormStateInterface $form_state);

  /**
   * Export Handler Callback.
   *
   * @param array $info
   *   Info array.
   * @param array|null $context
   *   Context array.
   */
  public function processExport(array $info, ?array &$context);

}
