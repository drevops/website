<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\MediaGeneratorBase;
use Drupal\file\FileInterface;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated image media entities.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_media_civictheme_image',
  entity_type: 'media',
  bundle: 'civictheme_image',
  weight: 1,
  tracking: TRUE,
)]
class MediaCivicthemeImage extends MediaGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const FIELD_NAME = 'field_c_m_image';

  /**
   * {@inheritdoc}
   */
  protected const EXTENSIONS = ['jpg', 'png'];

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'image';

  /**
   * {@inheritdoc}
   */
  protected function fileValues(FileInterface $file): array {
    return [
      static::FIELD_NAME => [
        'target_id' => $file->id(),
        'alt' => $this->helper::staticSentence(3),
      ],
    ];
  }

}
