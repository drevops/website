<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\CaseMatrix;
use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\do_generated_content\Generator\RelativeDate;
use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Generated project nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_project',
  entity_type: 'node',
  bundle: 'project',
  weight: 33,
  tracking: TRUE,
)]
class NodeProject extends NodeGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'project';

  /**
   * Roles a delivery team fills, walked so multi-value rendering is covered.
   */
  protected const ROLES = [
    'Technical lead',
    'Solution architect',
    'Drupal developer',
    'DevOps engineer',
    'Site reliability engineer',
  ];

  /**
   * Pages a run can offer as services, loaded once per run.
   *
   * @var \Drupal\node\NodeInterface[]|null
   */
  protected ?array $servicePagesCache = NULL;

  /**
   * {@inheritdoc}
   */
  protected function bundleValues(int $index): array {
    return $this->commonValues($index) + $this->bannerValues($index) + $this->pageValues($index) + $this->projectValues($index);
  }

  /**
   * Build the fields only a project carries.
   *
   * @return array
   *   Field values.
   */
  protected function projectValues(int $index): array {
    $year = (int) RelativeDate::format('now', 'Y') - ($index % 6);

    $values = [
      'field_do_n_client' => sprintf('Generated Client %s', $index + 1),
      'field_do_n_delivered_at' => sprintf('%s %s', CaseMatrix::cycle(['March', 'July', 'November'], $index), $year),
      'field_do_n_year' => $year,
      'field_do_n_status' => $this->nodeOption('field_do_n_status', $index),
      'field_do_n_role' => CaseMatrix::subset(self::ROLES, $index, [1, 2, 3]),
      'field_do_n_live_url' => ['uri' => 'internal:/', 'title' => 'View the live site'],
    ];

    $contributions = CaseMatrix::cycle([0, 1, 3], $index);

    for ($i = 0; $i < $contributions; $i++) {
      $values['field_do_n_oss_contributions'][] = ['uri' => 'internal:/', 'title' => sprintf('Contribution %s', $i + 1)];
    }

    $sectors = $this->vocabularyTerms('do_sector');

    if ($sectors !== []) {
      $values['field_do_n_sector'] = ['target_id' => CaseMatrix::cycle($sectors, $index)->id()];
    }

    $values['field_do_n_services'] = $this->servicePageTargets($index);
    $values['field_do_n_technologies'] = $this->termTargets('do_technology', $index, [1, 3, 5]);

    return $values;
  }

  /**
   * Build entity reference values for a walked number of service pages.
   *
   * @param int $index
   *   Zero-based run index.
   *
   * @return array<int, array<string, int|string|null>>
   *   Field values.
   */
  protected function servicePageTargets(int $index): array {
    $this->servicePagesCache ??= $this->servicePages();

    if ($this->servicePagesCache === []) {
      return [];
    }

    $pages = CaseMatrix::subset($this->servicePagesCache, $index, [1, 2, 3]);

    return array_map(static fn(NodeInterface $node): array => ['target_id' => $node->id()], $pages);
  }

  /**
   * Load the pages the site offers as services.
   *
   * The site groups these pages under one path rather than marking them with a
   * field, so the alias is what separates a service from any other page. A
   * site that has none yet generates projects with no services, which is what
   * an author would see before writing the pages.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The service pages, in storage order.
   */
  protected function servicePages(): array {
    $alias_storage = $this->entityTypeManager->getStorage('path_alias');
    $ids = $alias_storage->getQuery()->accessCheck(FALSE)->condition('alias', '/services/%', 'LIKE')->execute();
    $aliases = $alias_storage->loadMultiple($ids);
    $nids = [];

    foreach ($aliases as $alias) {
      if (preg_match('#^/node/(\d+)$#', (string) $alias->getPath(), $matches)) {
        $nids[] = (int) $matches[1];
      }
    }

    if ($nids === []) {
      return [];
    }

    // Unpublished pages are left out because the "At a glance" panel drops what
    // the reader cannot view, and a run that picked only those would render no
    // Services row at all.
    return array_values($this->entityTypeManager->getStorage('node')->loadByProperties([
      'nid' => $nids,
      'type' => 'civictheme_page',
      'status' => NodeInterface::PUBLISHED,
    ]));
  }

  /**
   * Build entity reference values for a walked number of terms.
   *
   * @param string $vocabulary
   *   Vocabulary id to draw terms from.
   * @param int $index
   *   Zero-based run index.
   * @param array $sizes
   *   Numbers of terms to walk.
   *
   * @return array<int, array<string, int|string|null>>
   *   Field values.
   */
  protected function termTargets(string $vocabulary, int $index, array $sizes): array {
    $terms = CaseMatrix::subset($this->vocabularyTerms($vocabulary), $index, $sizes);

    return array_map(static fn(TermInterface $term): array => ['target_id' => $term->id()], $terms);
  }

}
