<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\do_generated_content\Generator\NodeGeneratorBase;
use Drupal\generated_content\Attribute\GeneratedContent;

/**
 * Generated blog post nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_blog',
  entity_type: 'node',
  bundle: 'blog',
  weight: 32,
  tracking: TRUE,
)]
class NodeBlog extends NodeGeneratorBase {

  /**
   * {@inheritdoc}
   */
  protected const LABEL = 'blog post';

  /**
   * {@inheritdoc}
   */
  protected function bundleValues(int $index): array {
    return $this->commonValues($index) + $this->bannerValues($index) + $this->pageValues($index);
  }

}
