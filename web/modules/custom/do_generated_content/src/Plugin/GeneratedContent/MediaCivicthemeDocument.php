<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\MediaGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated document media entities.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_media_civictheme_document',
  entity_type: 'media',
  bundle: 'civictheme_document',
  weight: 2,
  tracking: TRUE,
)]
class MediaCivicthemeDocument extends MediaGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const COUNT = 8;

  /**
   * {@inheritdoc}
   */
  protected const FIELD_NAME = 'field_c_m_document';

  /**
   * {@inheritdoc}
   */
  protected const EXTENSIONS = ['pdf', 'docx'];

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'document';

}
