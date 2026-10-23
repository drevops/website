<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\Plugin\media\Source\File;

/**
 * Hooks that let SVG media display their own artwork.
 */
final class SvgMediaHook {

  /**
   * MIME type of an SVG file.
   */
  private const string MIME_TYPE = 'image/svg+xml';

  /**
   * Implements hook_media_presave().
   */
  #[Hook('media_presave')]
  public function presave(MediaInterface $media): void {
    $source = $media->getSource();

    if (!$source instanceof File) {
      return;
    }

    $file = $media->get($source->getConfiguration()['source_field'])->entity;

    if (!$this->isSvg($file)) {
      return;
    }

    // The file source gives every item the same generic placeholder thumbnail.
    $media->set('thumbnail', [
      'target_id' => $file->id(),
      'alt' => $media->label(),
      'width' => NULL,
      'height' => NULL,
    ]);
  }

  /**
   * Implements hook_preprocess_HOOK() for image_formatter.
   */
  #[Hook('preprocess_image_formatter')]
  public function preprocessImageFormatter(array &$variables): void {
    if (($variables['image']['#theme'] ?? NULL) !== 'image_style' || !$this->isSvg($variables['item']->entity ?? NULL)) {
      return;
    }

    // The image toolkit cannot rasterise an SVG, so an image style renders
    // nothing and logs a warning on every view.
    $variables['image']['#theme'] = 'image';
    unset($variables['image']['#style_name']);
  }

  /**
   * Checks whether a value is an SVG file.
   *
   * @phpstan-assert-if-true \Drupal\file\FileInterface $file
   */
  protected function isSvg(mixed $file): bool {
    return $file instanceof FileInterface && $file->getMimeType() === self::MIME_TYPE;
  }

}
