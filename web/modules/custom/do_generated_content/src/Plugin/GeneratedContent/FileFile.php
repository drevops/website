<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Helpers\GeneratedContentAssetGenerator;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;

/**
 * Generated files.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_file_file',
  entity_type: 'file',
  bundle: 'file',
  weight: -10,
  tracking: TRUE,
)]
class FileFile extends GeneratedContentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    $types = [
      GeneratedContentAssetGenerator::ASSET_TYPE_JPG,
      GeneratedContentAssetGenerator::ASSET_TYPE_PNG,
    ];

    for ($i = 0; $i < 20; $i++) {
      $type = $this->helper::randomArrayItem($types);
      $width = $this->helper::randomBool() ? 1200 : 800;
      $height = $this->helper::randomBool() ? 600 : 400;

      $file = $this->helper::createFile($type, [
        'width' => $width,
        'height' => $height,
      ]);

      $entities[] = $file;
      $this->helper::log('Created file: %s', $file->getFilename());
    }

    return $entities;
  }

}
