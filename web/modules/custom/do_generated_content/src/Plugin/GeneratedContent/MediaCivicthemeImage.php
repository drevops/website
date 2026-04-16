<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\file\FileInterface;
use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\media\Entity\Media;

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
class MediaCivicthemeImage extends GeneratedContentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    for ($i = 0; $i < 10; $i++) {
      $file = $this->helper::randomFile('jpg');

      if (!$file instanceof FileInterface) {
        $file = $this->helper::randomFile('png');
      }

      if (!$file instanceof FileInterface) {
        continue;
      }

      $name = sprintf('Generated image %s', $i + 1);

      $media = Media::create([
        'bundle' => 'civictheme_image',
        'name' => $name,
        'field_c_m_image' => [
          'target_id' => $file->id(),
          'alt' => $this->helper::staticSentence(3),
        ],
      ]);

      $media->save();
      $entities[] = $media;

      $this->helper::log('Created media: %s', $name);
    }

    return $entities;
  }

}
