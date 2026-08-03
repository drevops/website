<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\TaxonomyTermGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated site section taxonomy terms.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_taxonomy_term_civictheme_site_sections',
  entity_type: 'taxonomy_term',
  bundle: 'civictheme_site_sections',
  weight: 12,
  tracking: TRUE,
)]
class TaxonomyTermCivicthemeSiteSections extends TaxonomyTermGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const NAMES = [
    'About',
    'Services',
    'Work',
    'Insights',
  ];

}
