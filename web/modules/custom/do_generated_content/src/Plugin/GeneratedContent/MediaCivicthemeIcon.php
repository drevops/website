<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\MediaGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated icon media entities.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_media_civictheme_icon',
  entity_type: 'media',
  bundle: 'civictheme_icon',
  weight: 3,
  tracking: TRUE,
)]
class MediaCivicthemeIcon extends MediaGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const COUNT = 8;

  /**
   * {@inheritdoc}
   */
  protected const FIELD_NAME = 'field_c_m_icon';

  /**
   * {@inheritdoc}
   */
  protected const EXTENSIONS = ['svg'];

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'icon';

}
