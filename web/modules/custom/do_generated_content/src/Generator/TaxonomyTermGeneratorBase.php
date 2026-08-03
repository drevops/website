<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Base class for generators of flat vocabularies.
 *
 * Terms are named rather than generated: a project referencing 'Drupal' and
 * 'Health' reads as real content, where a lorem ipsum term does not.
 */
abstract class TaxonomyTermGeneratorBase extends GeneratedContentPluginBase {

  /**
   * Term names to create, in order.
   */
  protected const NAMES = [];

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    foreach (static::NAMES as $name) {
      $term = Term::create(['vid' => $this->getBundle(), 'name' => $name]);
      $term->save();

      $entities[] = $term;

      $this->helper::log('Created %s term: %s', $this->getBundle(), $name);
    }

    return $entities;
  }

}
