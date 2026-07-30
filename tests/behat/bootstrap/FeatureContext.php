<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\AccessibilityTrait;
use DrevOps\BehatSteps\CookieTrait;
use DrevOps\BehatSteps\DateTrait;
use DrevOps\BehatSteps\Drupal\BlockTrait;
use DrevOps\BehatSteps\Drupal\ContentBlockTrait;
use DrevOps\BehatSteps\Drupal\ContentTrait;
use DrevOps\BehatSteps\Drupal\DraggableviewsTrait;
use DrevOps\BehatSteps\Drupal\EckTrait;
use DrevOps\BehatSteps\Drupal\EmailTrait;
use DrevOps\BehatSteps\Drupal\FileTrait;
use DrevOps\BehatSteps\Drupal\MediaTrait;
use DrevOps\BehatSteps\Drupal\MenuTrait;
use DrevOps\BehatSteps\MetatagTrait;
use DrevOps\BehatSteps\Drupal\OverrideTrait;
use DrevOps\BehatSteps\Drupal\ParagraphsTrait;
use DrevOps\BehatSteps\Drupal\SearchApiTrait;
use DrevOps\BehatSteps\Drupal\TaxonomyTrait;
use DrevOps\BehatSteps\Drupal\TestmodeTrait;
use DrevOps\BehatSteps\Drupal\UserTrait;
use DrevOps\BehatSteps\Drupal\WatchdogTrait;
use DrevOps\BehatSteps\ElementTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\FileDownloadTrait;
use DrevOps\BehatSteps\JavascriptTrait;
use DrevOps\BehatSteps\KeyboardTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\ResponsiveTrait;
use DrevOps\BehatSteps\WaitTrait;
use Behat\Step\Given;
use Behat\Step\Then;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoState;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use AccessibilityTrait;
  use BlockTrait;
  use ContentBlockTrait;
  use ContentTrait;
  use CookieTrait;
  use DateTrait;
  use DraggableviewsTrait;
  use EckTrait;
  use ElementTrait;
  use EmailTrait;
  use FieldTrait;
  use FileDownloadTrait;
  use FileTrait;
  use JavascriptTrait;
  use KeyboardTrait;
  use LinkTrait;
  use MediaTrait;
  use MenuTrait;
  use MetatagTrait;
  use OverrideTrait;
  use ParagraphsTrait;
  use PathTrait;
  use ResponseTrait;
  use ResponsiveTrait;
  use SearchApiTrait;
  use TaxonomyTrait;
  use TestmodeTrait;
  use UserTrait;
  use WaitTrait;
  use WatchdogTrait;

  /**
   * Set an explicit path alias for a node, bypassing pathauto.
   *
   * Pathauto regenerates the alias from the configured pattern on save, so a
   * nested alias cannot be created through the standard content steps. Skipping
   * pathauto for this node preserves the explicit alias.
   *
   * @code
   * Given the "civictheme_page" content "[TEST] Article" has the path alias "/section/article"
   * @endcode
   */
  #[Given('the :content_type content :title has the path alias :alias')]
  public function contentSetPathAlias(string $content_type, string $title, string $alias): void {
    // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => $content_type,
      'title' => $title,
    ]);

    if (count($nodes) !== 1) {
      throw new \RuntimeException(sprintf('Expected exactly one "%s" content item with the title "%s", but found %d.', $content_type, $title, count($nodes)));
    }

    $node = reset($nodes);

    if (!$node instanceof NodeInterface) {
      throw new \RuntimeException(sprintf('The "%s" content with the title "%s" is not a node.', $content_type, $title));
    }

    $node->set('path', ['alias' => $alias, 'pathauto' => PathautoState::SKIP]);
    $node->save();
  }

  /**
   * Assert that a content item is published.
   *
   * Checks both the published flag and the moderation state. The content is
   * reloaded from storage because the UI action that published it ran in a
   * separate web request, leaving this process's entity cache stale.
   *
   * @code
   * Then the "civictheme_page" content "[TEST] Article" should be published
   * @endcode
   */
  #[Then('the :content_type content :title should be published')]
  public function contentShouldBePublished(string $content_type, string $title): void {
    // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $nids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $content_type)
      ->condition('title', $title)
      ->execute();

    if (count($nids) !== 1) {
      throw new \RuntimeException(sprintf('Expected exactly one "%s" content item with the title "%s", but found %d.', $content_type, $title, count($nids)));
    }

    $nid = (int) reset($nids);
    $storage->resetCache([$nid]);
    $node = $storage->load($nid);

    if (!$node instanceof NodeInterface) {
      throw new \RuntimeException(sprintf('Unable to load "%s" content with the title "%s".', $content_type, $title));
    }

    $state = $node->get('moderation_state')->value;

    if ($state !== 'published' || !$node->isPublished()) {
      throw new \RuntimeException(sprintf('Expected "%s" to be published, but its moderation state is "%s" and its published flag is %s.', $title, $state, $node->isPublished() ? 'true' : 'false'));
    }
  }

  /**
   * Assert that an element paints on top of another element.
   *
   * Compares the computed z-index of two elements that both establish a
   * root-level stacking context. A descendant cannot escape its ancestor's
   * stacking context, so this also settles the order of any popup opened
   * inside either element.
   *
   * @code
   * Then the element ".top-bar" should stack above the element ".ct-header"
   * @endcode
   *
   * @javascript
   */
  #[Then('the element :selector should stack above the element :other_selector')]
  public function elementAssertStacksAbove(string $selector, string $other_selector): void {
    $above = $this->elementZindex($selector);
    $below = $this->elementZindex($other_selector);

    if ($above <= $below) {
      throw new \RuntimeException(sprintf('Expected element "%s" to stack above element "%s", but their z-index values are %d and %d.', $selector, $other_selector, $above, $below));
    }
  }

  /**
   * Get the computed z-index of an element.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @return int
   *   The computed z-index.
   */
  protected function elementZindex(string $selector): int {
    $script = <<<JS
      return window.getComputedStyle({{ELEMENT}}).zIndex;
JS;
    $z_index = (string) $this->elementExecuteJs($selector, $script);

    // An element without a stacking order of its own cannot be compared, and
    // silently treating it as zero would assert something that was never
    // styled.
    if (!is_numeric($z_index)) {
      throw new \RuntimeException(sprintf('Expected element "%s" to have a numeric z-index, but it is "%s".', $selector, $z_index));
    }

    return (int) $z_index;
  }

}
