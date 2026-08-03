<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\CaseMatrix;
use Drupal\do_generated_content\Generator\Formats;
use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\do_generated_content\Generator\RelativeDate;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated alert nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_civictheme_alert',
  entity_type: 'node',
  bundle: 'civictheme_alert',
  weight: 34,
  tracking: TRUE,
)]
class NodeCivicthemeAlert extends NodeGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'alert';

  /**
   * Number of alerts left visible on every page.
   *
   * An alert with no page restriction renders on the whole site, so all but a
   * couple are scoped to a path the generated content does not use.
   */
  protected const SITE_WIDE_COUNT = 2;

  /**
   * Offsets producing an expired, a running and an upcoming alert.
   */
  protected const SCHEDULES = [
    ['-30 days', '-15 days'],
    ['-1 day', '+14 days'],
    ['+15 days', '+30 days'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function bundleValues(int $index): array {
    $schedule = CaseMatrix::cycle(self::SCHEDULES, $index);

    $values = [
      'field_c_n_alert_type' => $this->nodeOption('field_c_n_alert_type', $index),
      'field_c_n_body' => [
        'value' => $this->helper::staticHtmlParagraph(),
        'format' => Formats::TEXT,
      ],
      'field_c_n_date_range' => [
        'value' => RelativeDate::format($schedule[0], Formats::DATETIME),
        'end_value' => RelativeDate::format($schedule[1], Formats::DATETIME),
      ],
    ];

    if ($index >= self::SITE_WIDE_COUNT) {
      $values['field_c_n_alert_page_visibility'] = "/generated-alert-scope\n/generated-alert-scope/*";
    }

    return $values;
  }

}
