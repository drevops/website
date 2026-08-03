<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\TaxonomyTermGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated technology taxonomy terms.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_taxonomy_term_do_technology',
  entity_type: 'taxonomy_term',
  bundle: 'do_technology',
  weight: 15,
  tracking: TRUE,
)]
class TaxonomyTermDoTechnology extends TaxonomyTermGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const NAMES = [
    'Drupal',
    'PHP',
    'Docker',
    'Kubernetes',
    'Terraform',
    'GitHub Actions',
    'CircleCI',
    'Solr',
    'Redis',
    'Behat',
    'PHPUnit',
    'Lagoon',
  ];

}
