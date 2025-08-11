<?php

namespace Drupal\views_fieldsets;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Markup;
use Drupal\views\ResultRow;
use Drupal\views_fieldsets\Plugin\views\field\Fieldset;

/**
 * Custom class for handling RowFieldset.
 */
class RowFieldset {

  /**
   * Current row being worked.
   *
   * @var \Drupal\views\ResultRow
   */
  public ResultRow $row;

  /**
   * An array of properties for fieldset.
   *
   * @var array
   */
  public array $properties = [];

  /**
   * An array children for fieldset.
   *
   * @var array
   */
  public array $children = [];

  /**
   * Constructs an RowFieldset object.
   */
  public function __construct($field, ResultRow $row) {
    $this->row = $row;
    $this->properties = get_object_vars($field);
  }

  /**
   * Magic method: __isset a property value.
   *
   * @param string $name
   *   Method's name.
   */
  public function __isset(string $name) {
    return TRUE;
  }

  /**
   * Magic method: Gets a property value.
   *
   * @param string $name
   *   Method's name.
   */
  public function __get(string $name) {
    $method_name = 'get' . Unicode::ucwords($name);
    if (is_callable($method = [$this, $method_name])) {
      return call_user_func($method);
    }
    if (!empty($name) && !empty($this->properties[$name])) {
      return $this->properties[$name];
    }
    return FALSE;
  }

  /**
   * Object getcontent().
   */
  public function getContent() {
    return $this->render();
  }

  /**
   * Object getwrapperelement().
   */
  public function getWrapperelement() {
    return '';
  }

  /**
   * Object getelementtype().
   */
  public function getElementtype() {
    return '';
  }

  /**
   * Object render().
   */
  public function render() {
    $children = $this->children;
    $show_fieldset = FALSE;
    foreach ($children as $child) {
      if (isset($child->content)) {
        $text = trim(strip_tags($child->content));
        if (!empty($text)) {
          $show_fieldset = TRUE;
          break;
        }
      }
    }
    $element = [
      '#theme' => $this->themeFunctions($this->getWrapperType()),
      '#fields' => $this->children,
      '#show_fieldset' => $show_fieldset,
      '#legend' => Markup::create($this->getLegend()),
      '#collapsible' => (bool) $this->handler->options['collapsible'],
      '#attributes' => [
        'class' => $this->getClasses(),
      ],
    ];
    if ($this->handler->options['collapsed'] && $this->getWrapperType() != 'div') {
      $element['#attributes']['class'][] = 'collapsed';
    }
    // @phpstan-ignore-next-line
    return \Drupal::service('renderer')->render($element);
  }

  /**
   * Object getWrapperType().
   */
  protected function getWrapperType() {
    $allowed = Fieldset::getWrapperTypes();
    $wrapper = $this->handler->options['wrapper'];
    if (isset($allowed[$wrapper])) {
      return $wrapper;
    }

    reset($allowed);
    return key($allowed);
  }

  /**
   * Object getLegend().
   */
  protected function getLegend() {
    return $this->tokenize($this->handler->options['legend']);
  }

  /**
   * Object getClasses().
   */
  protected function getClasses() {
    $classes = preg_split('/(,|  )/', $this->handler->options['classes']);
    return array_map(function ($class) {
      return Html::getClass($this->tokenize($class));
    }, $classes);
  }

  /**
   * Object tokenize().
   *
   * @param string $string
   *   String.
   */
  protected function tokenize($string) {
    return $this->handler->tokenizeValue($string, $this->row->index);
  }

  /**
   * Object addChild().
   *
   * @param array $fields
   *   Fields.
   * @param string $field_name
   *   Field name.
   */
  public function addChild(array $fields, $field_name) {
    $this->children[$field_name] = $fields[$field_name];
  }

  /**
   * Generate a list of theme hook suggestions.
   *
   * @param string $type
   *   Fieldset type.
   *
   * @return array
   *   List of theme suggestions.
   */
  public function themeFunctions(string $type): array {
    $themes = [];
    $hook = 'views_fieldsets_' . $type;

    $display = $this->handler->view->display_handler->display;

    if (!empty($display)) {
      $themes[] = $hook . '__' . $this->handler->view->storage->id() . '__' . $display['id'] . '__' . $this->handler->options['id'];
      $themes[] = $hook . '__' . $this->handler->view->storage->id() . '__' . $display['id'];
      $themes[] = $hook . '__' . $display['id'] . '__' . $this->handler->options['id'];
      $themes[] = $hook . '__' . $display['id'];
      if ($display['id'] != $display['display_plugin']) {
        $themes[] = $hook . '__' . $this->handler->view->storage->id() . '__' . $display['display_plugin'] . '__' . $this->handler->options['id'];
        $themes[] = $hook . '__' . $this->handler->view->storage->id() . '__' . $display['display_plugin'];
        $themes[] = $hook . '__' . $display['display_plugin'] . '__' . $this->handler->options['id'];
        $themes[] = $hook . '__' . $display['display_plugin'];
      }
    }
    $themes[] = $hook . '__' . $this->handler->view->storage->id() . '__' . $this->handler->options['id'];
    $themes[] = $hook . '__' . $this->handler->view->storage->id();
    $themes[] = $hook . '__' . $this->handler->options['id'];
    $themes[] = $hook;

    return $themes;
  }

}
