<?php

namespace Drupal\Tests\linkit\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the linkit alterations on the drupallink plugin.
 *
 * @group linkit
 */
class LinkitDialogAnchorTest extends LinkitDialogTest {

  /**
   * Test the link dialog.
   */
  public function testLinkAnchorDialog() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    $test_anchor = '#testanchor';

    // Adds additional languages.
    $langcodes = ['sv', 'da', 'fi'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Create a test entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = EntityTestMul::create(['name' => 'Foo']);
    $entity->save();

    // Go to node creation page.
    $this->drupalGet('node/add/page');

    // Wait until the editor has been loaded.
    $ckeditor_loaded = $this->getSession()->wait(5000, "jQuery('.cke_contents').length > 0");
    $this->assertTrue($ckeditor_loaded, 'The editor has been loaded.');

    // Click on the drupallink plugin.
    $page->find('css', 'a.cke_button__drupallink')->click();

    // Wait for the form to load.
    $web_assert->assertWaitOnAjaxRequest();

    // Find the href field.
    $href_field = $page->findField('attributes[href]');

    // Make sure the href field is an autocomplete field.
    $href_field->hasAttribute('data-autocomplete-path');
    $href_field->hasClass('form-linkit-autocomplete');
    $href_field->hasClass('ui-autocomplete-input');

    // Make sure all fields are empty.
    $this->assertEmpty($href_field->getValue(), 'Href field is empty.');
    $this->assertEmptyWithJs('attributes[data-entity-type]');
    $this->assertEmptyWithJs('attributes[data-entity-uuid]');
    $this->assertEmptyWithJs('attributes[data-entity-substitution]');
    $this->assertEmptyWithJs('href_dirty_check');

    // Make sure the autocomplete result container is hidden.
    $autocomplete_container = $page->find('css', 'ul.linkit-ui-autocomplete');
    $this->assertFalse($autocomplete_container->isVisible());

    // Trigger a keydown event to active a autocomplete search.
    $href_field->setValue('f');
    $this->getSession()->getDriver()->keyDown($href_field->getXpath(), ' ');

    // Wait for the results to load.
    $this->getSession()->wait(5000, "jQuery('.linkit-result-line.ui-menu-item').length > 0");

    // Make sure the autocomplete result container is visible.
    $this->assertTrue($autocomplete_container->isVisible());

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.linkit-result-line.ui-menu-item');
    $this->assertEquals(1, count($results), 'Found autocomplete result');

    // Find the first result and click it.
    $page->find('xpath', '//li[contains(@class, "linkit-result-line") and contains(@class, "ui-menu-item")][1]')->click();
    $href_field->setValue($href_field->getValue('f') . $test_anchor);

    // Make sure the linkit field field is populated with the node url.
    $this->assertEquals($entity->toUrl()->toString() . $test_anchor, $href_field->getValue(), 'The href field is populated with the node url.');

    // Make sure all other fields are populated.
    $this->assertEqualsWithJs('attributes[data-entity-type]', $entity->getEntityTypeId());
    $this->assertEqualsWithJs('attributes[data-entity-uuid]', $entity->uuid());
    $this->assertEqualsWithJs('attributes[data-entity-substitution]', 'canonical');
    $this->assertEqualsWithJs('href_dirty_check', $entity->toUrl()->toString());
    $this->assertEqualsWithJs('attributes[href]', $entity->toUrl()->toString() . $test_anchor);

    // Save the dialog input.
    $this->click('.editor-link-dialog button:contains("Save")');

    // Wait for the dialog to close.
    $web_assert->assertWaitOnAjaxRequest();

    $fields = [
      'data-entity-type' => $entity->getEntityTypeId(),
      'data-entity-uuid' => $entity->uuid(),
      'data-entity-substitution' => 'canonical',
      'href' => $entity->toUrl()->toString() . $test_anchor,
    ];
    foreach ($fields as $attribute => $value) {
      $link_attribute = $this->getLinkAttributeFromEditor($attribute);
      $this->assertEquals($value, $link_attribute, 'The link contain an attribute by the name of "' . $attribute . '" with a value of "' . $value . '"');
    }

    // Select the link in the editor.
    $javascript = <<<JS
      (function(){
        var editor = window.CKEDITOR.instances['edit-body-0-value'];
        console.log(editor);
        var element = editor.document.findOne( 'a' );
        editor.getSelection().selectElement( element );
      })()
JS;
    $session->executeScript($javascript);

    // Click on the drupallink plugin.
    $page->find('css', 'a.cke_button__drupallink')->click();

    // Wait for the form to load.
    $web_assert->assertWaitOnAjaxRequest();

    // Find the href field.
    $href_field = $page->findField('attributes[href]');
    $this->assertEquals($entity->toUrl()->toString() . $test_anchor, $href_field->getValue(), 'Href field contains the node url when edit.');

    // Make sure all other fields are populated when editing a link.
    $this->assertEqualsWithJs('attributes[data-entity-type]', $entity->getEntityTypeId());
    $this->assertEqualsWithJs('attributes[data-entity-uuid]', $entity->uuid());
    $this->assertEqualsWithJs('attributes[data-entity-substitution]', 'canonical');
    $this->assertEqualsWithJs('href_dirty_check', $entity->toUrl()->toString() . $test_anchor);

    // Edit the href field and set an external url.
    $href_field->setValue('http://example.com' . $test_anchor);

    // Save the dialog input.
    $this->click('.editor-link-dialog button:contains("Save")');

    // Wait for the dialog to close.
    $web_assert->assertWaitOnAjaxRequest();

    $fields = [
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ];
    foreach ($fields as $attribute) {
      $link_attribute = $this->getLinkAttributeFromEditor($attribute);
      $this->assertNull($link_attribute, 'The link does not contain an attribute by the name of "' . $attribute . '"');
    }

    $href_attribute = $this->getLinkAttributeFromEditor('href');
    $this->assertEquals('http://example.com' . $test_anchor, $href_attribute, 'The link href is correct.');
  }

}
