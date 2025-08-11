<?php
/**
 * @see https://github.com/leesherwood/Orientation-Fix-PHP/blob/master/fix_orientation.php
 * Автоматично перевертає картинку по даних EXIF
 */
namespace Drupal\other\Plugin\ImageEffect;

use Drupal\Component\Utility\Rectangle;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;

/**
 * Converts an image resource.
 *
 * @ImageEffect(
 *   id = "image_autorotate",
 *   label = @Translation("AutoRotate"),
 *   description = @Translation("AutoRotate image (of EXIF data).")
 * )
 */
class AutoRotateEffect extends ImageEffectBase {
  
  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if ( function_exists('exif_read_data') ) {
      $exif = @exif_read_data(\Drupal::service('file_system')->realpath($image->getSource()), 'IFD0');
      if( empty($exif) || !is_array($exif) ){
        return false;
      }
      $exif = array_change_key_case($exif, CASE_LOWER);
      if( !array_key_exists('orientation', $exif) ){
        return false;
      }
      switch($exif['orientation']) {
        case 1:
          return true;
        case 2:
          $this->flip($image, IMG_FLIP_HORIZONTAL);
          break;
        case 3:
          $this->flip($image, IMG_FLIP_VERTICAL);
          $this->flip($image, IMG_FLIP_HORIZONTAL);
          break;
        case 4:
          $this->flip($image, IMG_FLIP_VERTICAL);
          break;
        case 5:
          $this->flip($image, IMG_FLIP_VERTICAL);
        case 6:
          $image->rotate(90, 0);
          break;
        case 7:
          $this->flip($image, IMG_FLIP_VERTICAL);
        case 8:
          $image->rotate(-90, 0);
          break;
      }
    }
    return true;
  }
  
  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    $exif = @exif_read_data(\Drupal::service('file_system')->realpath($uri), 'IFD0');
    if( empty($exif) || !is_array($exif) ){
      return false;
    }
    $exif = array_change_key_case($exif, CASE_LOWER);
    if( !array_key_exists('orientation', $exif) ){
      return false;
    }

    switch($exif['orientation']) {
      case 1:
      case 2:
      case 3:
      case 4:
        return;
      case 5:
      case 6:
      case 7:
      case 8:
        if ( $dimensions['width'] && $dimensions['height']) {
          $rect = new Rectangle($dimensions['width'], $dimensions['height']);
          $rect = $rect->rotate(90);
          $dimensions['width'] = $rect->getBoundingWidth();
          $dimensions['height'] = $rect->getBoundingHeight();
        } else {
          $dimensions['width'] = $dimensions['height'] = NULL;
        }
        break;
    }
  }
  
  /**
   * {@inheritdoc}
   */
  protected function flip($image, $mode){
    if ( imageflip($image->getToolkit()->getResource(), $mode)) {
      return TRUE;
    }
  }

}

