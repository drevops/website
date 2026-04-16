<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\taxonomy\Entity\Term;

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
class TaxonomyTermCivicthemeTopics extends GeneratedContentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    $topics = [
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

    foreach ($topics as $topic) {
      $term = Term::create([
        'vid' => 'civictheme_topics',
        'name' => $topic,
      ]);

      $term->save();
      $entities[] = $term;

      $this->helper::log('Created topic: %s', $topic);
    }

    return $entities;
  }

}
