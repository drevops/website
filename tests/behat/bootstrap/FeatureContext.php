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

}
