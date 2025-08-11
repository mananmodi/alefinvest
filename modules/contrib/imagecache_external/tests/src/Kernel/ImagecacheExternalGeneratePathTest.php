<?php

namespace Drupal\Tests\imagecache_external\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the functionality of the imagecache_external_generate_path() function.
 *
 * @group imagecache_external
 */
class ImagecacheExternalGeneratePathTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'imagecache_external',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'system',
      'imagecache_external',
    ]);
  }

  /**
   * Tests the imagecache_external_generate_path() function.
   */
  public function testGeneratePathWithNullOrEmptyUrl() {
    $this->assertFalse(imagecache_external_generate_path(NULL));
    $this->assertFalse(imagecache_external_generate_path(''));
  }

}
