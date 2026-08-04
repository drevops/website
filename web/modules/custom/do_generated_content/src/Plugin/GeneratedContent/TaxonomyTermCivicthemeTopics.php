<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\TaxonomyTermGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated topic taxonomy terms.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_taxonomy_term_civictheme_topics',
  entity_type: 'taxonomy_term',
  bundle: 'civictheme_topics',
  weight: 11,
  tracking: TRUE,
)]
class TaxonomyTermCivicthemeTopics extends TaxonomyTermGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const NAMES = [
    'Drupal',
    'DevOps',
    'CI/CD',
    'Testing',
    'Automation',
    'Open Source',
    'Web Development',
    'Performance',
    'Security',
    'Accessibility',
  ];

}
