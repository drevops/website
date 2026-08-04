<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated page nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_civictheme_page',
  entity_type: 'node',
  bundle: 'civictheme_page',
  weight: 31,
  tracking: TRUE,
)]
class NodeCivicthemePage extends NodeGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'page';

  /**
   * {@inheritdoc}
   */
  protected function bundleValues(int $index): array {
    return $this->commonValues($index) + $this->bannerValues($index) + $this->pageValues($index);
  }

}
