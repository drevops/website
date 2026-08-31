<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

/**
 * Reads every term of a vocabulary.
 *
 * Generation draws terms from the whole vocabulary rather than from the terms
 * it created itself. The site's automated lists filter on terms that already
 * exist, so content tagged only with generated terms appears in none of them,
 * and on a site provisioned from a production database generation creates no
 * new terms at all.
 *
 * @codeCoverageIgnore
 */
trait VocabularyTermsTrait {

  /**
   * Terms of each vocabulary, keyed by vocabulary id.
   *
   * @var array<string, \Drupal\taxonomy\TermInterface[]>
   */
  protected array $vocabularyTermsCache = [];

  /**
   * Load every term of a vocabulary.
   *
   * @param string $vid
   *   Vocabulary id.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The terms, in storage order.
   */
  protected function vocabularyTerms(string $vid): array {
    $this->vocabularyTermsCache[$vid] ??= array_values($this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid]));

    return $this->vocabularyTermsCache[$vid];
  }

}
