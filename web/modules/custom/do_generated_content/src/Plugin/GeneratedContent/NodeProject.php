<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\CaseMatrix;
use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\do_generated_content\Generator\RelativeDate;
use Drupal\generated_content\Attribute\GeneratedContent;
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

    $sector = $this->helper::randomTerm('do_sector');

    if ($sector instanceof TermInterface) {
      $values['field_do_n_sector'] = ['target_id' => $sector->id()];
    }

    $values['field_do_n_services'] = $this->termTargets('do_service', $index, [1, 2, 3]);
    $values['field_do_n_technologies'] = $this->termTargets('do_technology', $index, [1, 3, 5]);

    return $values;
  }

  /**
   * Build entity reference values for a walked number of terms.
   *
   * @return array<int, array<string, int|string|null>>
   *   Field values.
   */
  protected function termTargets(string $vocabulary, int $index, array $sizes): array {
    $terms = $this->helper::randomTerms($vocabulary, CaseMatrix::cycle($sizes, $index));

    return array_map(static fn(TermInterface $term): array => ['target_id' => $term->id()], $terms);
  }

}
