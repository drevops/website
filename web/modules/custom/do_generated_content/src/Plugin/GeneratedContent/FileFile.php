<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\CaseMatrix;
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
   * How many files to create of each asset type.
   *
   * Every media bundle draws its files by extension, so a type missing here
   * leaves that bundle with nothing to reference.
   */
  protected const COUNTS = [
    GeneratedContentAssetGenerator::ASSET_TYPE_JPG => 12,
    GeneratedContentAssetGenerator::ASSET_TYPE_PNG => 12,
    GeneratedContentAssetGenerator::ASSET_TYPE_SVG => 8,
    GeneratedContentAssetGenerator::ASSET_TYPE_PDF => 6,
    GeneratedContentAssetGenerator::ASSET_TYPE_DOCX => 4,
  ];

  /**
   * Image dimensions walked so generated images are not all one shape.
   */
  protected const DIMENSIONS = [
    ['width' => 1200, 'height' => 600],
    ['width' => 800, 'height' => 400],
    ['width' => 600, 'height' => 600],
  ];

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    foreach (self::COUNTS as $type => $count) {
      for ($index = 0; $index < $count; $index++) {
        $file = $this->helper::createFile($type, CaseMatrix::cycle(self::DIMENSIONS, $index));

        $entities[] = $file;

        $this->helper::log('Created file: %s', $file->getFilename());
      }
    }

    return $entities;
  }

}
