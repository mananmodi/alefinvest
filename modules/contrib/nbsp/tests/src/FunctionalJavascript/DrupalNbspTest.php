<?php

namespace Drupal\Tests\nbsp\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * Ensure the NBSP CKeditor 4 dialog works.
 *
 * @group nbsp
 * @group nbsp_functional
 */
class DrupalNbspTest extends WebDriverTestBase {

  use CKEditorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'editor',
    'ckeditor',
    'nbsp',
  ];

  /**
   * We use the minimal profile because we want to test local action links.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Defines a CKEditor using the "Full HTML" filter.
   *
   * @var \Drupal\editor\EditorInterface
   */
  protected $editor;

  /**
   * Defines a "Full HTML" filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $editorFilterFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create text format.
    $this->editorFilterFormat = FilterFormat::create([
      'format'  => 'full_html',
      'name'    => 'Full HTML',
      'weight'  => 0,
      'filters' => [],
    ]);
    $this->editorFilterFormat->save();

    $this->editor = Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor',
    ]);
    $settings = [
      'toolbar' => [
        'rows' => [
          [
            [
              'name' => 'All the things',
              'items' => [
                'Source',
                'Bold',
                'Italic',
                'DrupalNbsp',
              ],
            ],
          ],
        ],
      ],
      'plugins' => [],
    ];
    $this->editor->setSettings($settings);
    $this->editor->save();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle'        => 'page',
      'label'         => 'Body',
      'settings'      => ['display_summary' => TRUE],
      'required'      => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle'           => 'page',
      'mode'             => 'default',
      'status'           => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    // Create a user for tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format full_html',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Ensure the CKeditor still works when NBSP plugin is enabled.
   */
  public function testEditorWorksWhenNbspEnabled() {
    $this->drupalGet('node/add/page');

    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    $assert_session = $this->assertSession();

    // Ensure CKeditor works properly.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_editable', 1000));

    // Ensure the button NBSP is visible.
    $this->getSession()->switchToIFrame();
    $this->assertNotEmpty($this->assertSession()
      ->waitForElementVisible('css', 'a.cke_button__drupalnbsp'));
  }

  /**
   * Ensure the CKeditor still works when NBSP plugin is not enabled.
   */
  public function testEditorWorksWhenNbspNotEnabled() {
    // Add a default class in the settings.
    $settings = [
      'toolbar' => [
        'rows' => [
          [
            [
              'name' => 'All the things',
              'items' => [
                'Bold',
                'Italic',
              ],
            ],
          ],
        ],
      ],
      'plugins' => [],
    ];
    $this->editor->setSettings($settings);
    $this->editor->save();

    $this->drupalGet('node/add/page');

    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session = $this->assertSession();

    // Ensure CKeditor works properly.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_editable', 1000));

    // Ensure the button NBSP is not visible.
    $this->getSession()->switchToIFrame();
    $this->assertEmpty($this->assertSession()
      ->waitForElementVisible('css', 'a.cke_button__drupalnbsp'));
  }

  /**
   * Tests using DurpalNbsp button to add non-breaking space into CKEditor.
   */
  public function testButton() {
    $this->drupalGet('node/add/page');
    $this->waitForEditor();

    $this->pressEditorButton('drupalnbsp');
    $this->pressEditorButton('source');

    $assert_session = $this->assertSession();
    $value = $assert_session->elementExists('css', 'textarea.cke_source')
      ->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $nbsp = $xpath->query('//nbsp')[0];
    $this->assertEquals(" ", $nbsp->firstChild->nodeValue);
  }

}
