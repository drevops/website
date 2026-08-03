<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\TaxonomyTermGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated sector taxonomy terms.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_taxonomy_term_do_sector',
  entity_type: 'taxonomy_term',
  bundle: 'do_sector',
  weight: 13,
  tracking: TRUE,
)]
class TaxonomyTermDoSector extends TaxonomyTermGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const NAMES = [
    'Government',
    'Health',
    'Education',
    'Finance',
    'Not-for-profit',
    'Utilities',
    'Transport',
    'Retail',
  ];

}
