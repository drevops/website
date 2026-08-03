<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\CaseMatrix;
use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\do_generated_content\Generator\RelativeDate;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated event nodes.
 *
 * Runs ahead of the page-like bundles: their reference cards can only point at
 * an event or a page, so with no events yet those cards go ungenerated.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_civictheme_event',
  entity_type: 'node',
  bundle: 'civictheme_event',
  weight: 30,
  tracking: TRUE,
)]
class NodeCivicthemeEvent extends NodeGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'event';

  /**
   * Offsets producing a finished, a running and an upcoming event.
   */
  protected const SCHEDULES = [
    ['-30 days', '-30 days +3 hours'],
    ['-1 hour', '+3 hours'],
    ['+30 days', '+30 days +3 hours'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function bundleValues(int $index): array {
    $schedule = CaseMatrix::cycle(self::SCHEDULES, $index);

    return $this->commonValues($index) + [
      'field_c_n_body' => [
        'value' => $this->helper::staticRichText(3),
        'format' => 'civictheme_rich_text',
      ],
      'field_c_n_date_range' => [
        'value' => RelativeDate::format($schedule[0], 'Y-m-d\TH:i:s'),
        'end_value' => RelativeDate::format($schedule[1], 'Y-m-d\TH:i:s'),
      ],
      'field_c_n_location' => $this->components($index, 'field_c_n_location', 1),
      'field_c_n_attachments' => $this->components($index, 'field_c_n_attachments', CaseMatrix::cycle([0, 1, 2], $index)),
    ];
  }

}
