<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

use Drupal\file\FileInterface;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\media\Entity\Media;

/**
 * Base class for media generators backed by a generated file.
 *
 * @codeCoverageIgnore
 */
abstract class MediaGeneratorBase extends GeneratedContentPluginBase {

  /**
   * Number of media entities to create.
   */
  protected const COUNT = 20;

  /**
   * Name of the file or image field on the bundle.
   */
  protected const FIELD_NAME = '';

  /**
   * File extensions to draw from, in preference order.
   */
  protected const EXTENSIONS = [];

  /**
   * Human-readable bundle name used in names and log lines.
   */
  protected const LABEL = 'media';

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    for ($index = 0; $index < static::COUNT; $index++) {
      $file = $this->file();

      if (!$file instanceof FileInterface) {
        $this->helper::log('Skipped %s %s: no %s file was generated.', static::LABEL, $index + 1, implode('/', static::EXTENSIONS));

        continue;
      }

      $name = sprintf('Generated %s %s', static::LABEL, $index + 1);

      $media = Media::create(['bundle' => $this->getBundle(), 'name' => $name] + $this->fileValues($file));
      $media->save();

      $entities[] = $media;

      $this->helper::log('Created %s: %s', static::LABEL, $name);
    }

    return $entities;
  }

  /**
   * Build the file reference values.
   *
   * @return array
   *   Field values.
   */
  protected function fileValues(FileInterface $file): array {
    return [static::FIELD_NAME => ['target_id' => $file->id()]];
  }

  /**
   * Get a generated file of one of the bundle's extensions.
   */
  protected function file(): ?FileInterface {
    foreach (static::EXTENSIONS as $extension) {
      $file = $this->helper::randomFile($extension);

      if ($file instanceof FileInterface) {
        return $file;
      }
    }

    return NULL;
  }

}
