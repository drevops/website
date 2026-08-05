<?php

declare(strict_types=1);

namespace Drupal\Tests\do_ai_alt_text\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Builds the media items cases run against.
 */
trait ImageMediaCreationTrait {

  /**
   * Creates a media item holding the fixture image.
   *
   * @param string $media_type_id
   *   Media type to create the item in.
   * @param string $field_name
   *   Image field to place the fixture image in.
   * @param string $alt
   *   Alt text the image starts out with.
   *
   * @return \Drupal\media\MediaInterface
   *   Saved media item.
   */
  protected function createImageMedia(string $media_type_id, string $field_name, string $alt): MediaInterface {
    $media = Media::create([
      'bundle' => $media_type_id,
      'name' => 'Test image',
      $field_name => ['target_id' => $this->createImageFile()->id(), 'alt' => $alt],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Creates a file entity backed by the fixture image.
   *
   * @return \Drupal\file\Entity\File
   *   Saved file entity.
   */
  protected function createImageFile(): File {
    $file = File::create(['uri' => 'public://' . $this->randomMachineName() . '.png']);
    file_put_contents($file->getFileUri(), (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/image.png'));
    $file->save();

    return $file;
  }

  /**
   * Returns the source field name of a media type.
   *
   * @param string $media_type_id
   *   Media type to inspect.
   *
   * @return string
   *   Source field name.
   */
  protected function getSourceFieldName(string $media_type_id): string {
    $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($media_type_id);
    $this->assertInstanceOf(MediaTypeInterface::class, $media_type);

    return $media_type->getSource()->getConfiguration()['source_field'];
  }

  /**
   * Adds an extra image field to a media type.
   *
   * @param string $media_type_id
   *   Media type to extend.
   * @param string $field_name
   *   Name of the field to add.
   * @param int $cardinality
   *   Number of values the field holds.
   */
  protected function addImageField(string $media_type_id, string $field_name, int $cardinality): void {
    FieldStorageConfig::create([
      'entity_type' => 'media',
      'field_name' => $field_name,
      'type' => 'image',
      'cardinality' => $cardinality,
    ])->save();

    FieldConfig::create([
      'entity_type' => 'media',
      'bundle' => $media_type_id,
      'field_name' => $field_name,
      'settings' => ['alt_field' => TRUE],
    ])->save();
  }

}
