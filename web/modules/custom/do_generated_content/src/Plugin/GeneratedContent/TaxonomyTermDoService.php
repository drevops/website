<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\TaxonomyTermGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated service taxonomy terms.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_taxonomy_term_do_service',
  entity_type: 'taxonomy_term',
  bundle: 'do_service',
  weight: 14,
  tracking: TRUE,
)]
class TaxonomyTermDoService extends TaxonomyTermGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const NAMES = [
    'Drupal development',
    'Site migration',
    'DevOps automation',
    'Continuous integration',
    'Performance optimisation',
    'Security hardening',
    'Accessibility audit',
    'Support and maintenance',
  ];

}
