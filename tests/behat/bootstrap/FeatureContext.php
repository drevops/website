<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\AccessibilityTrait;
use DrevOps\BehatSteps\CommandTrait;
use DrevOps\BehatSteps\CookieTrait;
use DrevOps\BehatSteps\DateTrait;
use DrevOps\BehatSteps\DiagnosticsTrait;
use DrevOps\BehatSteps\Drupal\BigPipeTrait;
use DrevOps\BehatSteps\Drupal\BlockTrait;
use DrevOps\BehatSteps\Drupal\CacheTrait;
use DrevOps\BehatSteps\Drupal\ConfigTrait;
use DrevOps\BehatSteps\Drupal\ContentBlockTrait;
use DrevOps\BehatSteps\Drupal\ContentTrait;
use DrevOps\BehatSteps\Drupal\DraggableviewsTrait;
use DrevOps\BehatSteps\Drupal\EckTrait;
use DrevOps\BehatSteps\Drupal\EmailTrait;
use DrevOps\BehatSteps\Drupal\FileTrait;
use DrevOps\BehatSteps\Drupal\MediaTrait;
use DrevOps\BehatSteps\Drupal\MenuTrait;
use DrevOps\BehatSteps\Drupal\ModuleTrait;
use DrevOps\BehatSteps\MetatagTrait;
use DrevOps\BehatSteps\Drupal\OverrideTrait;
use DrevOps\BehatSteps\Drupal\ParagraphsTrait;
use DrevOps\BehatSteps\Drupal\QueueTrait;
use DrevOps\BehatSteps\Drupal\RedirectTrait;
use DrevOps\BehatSteps\Drupal\SearchApiTrait;
use DrevOps\BehatSteps\Drupal\StateTrait;
use DrevOps\BehatSteps\Drupal\TaxonomyTrait;
use DrevOps\BehatSteps\Drupal\TestmodeTrait;
use DrevOps\BehatSteps\Drupal\UserTrait;
use DrevOps\BehatSteps\Drupal\WatchdogTrait;
use DrevOps\BehatSteps\ElementTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\FileDownloadTrait;
use DrevOps\BehatSteps\IframeTrait;
use DrevOps\BehatSteps\JavascriptTrait;
use DrevOps\BehatSteps\JsonTrait;
use DrevOps\BehatSteps\KeyboardTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\ModalTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\ResponsiveTrait;
use DrevOps\BehatSteps\RestTrait;
use DrevOps\BehatSteps\TableTrait;
use DrevOps\BehatSteps\WaitTrait;
use DrevOps\BehatSteps\XmlTrait;
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
  use BigPipeTrait;
  use BlockTrait;
  use CacheTrait;
  use CommandTrait;
  use ConfigTrait;
  use ContentBlockTrait;
  use ContentTrait;
  use CookieTrait;
  use DateTrait;
  use DiagnosticsTrait;
  use DraggableviewsTrait;
  use EckTrait;
  use ElementTrait;
  use EmailTrait;
  use FieldTrait;
  use FileDownloadTrait;
  use FileTrait;
  use IframeTrait;
  use JavascriptTrait;
  use JsonTrait;
  use KeyboardTrait;
  use LinkTrait;
  use MediaTrait;
  use MenuTrait;
  use MetatagTrait;
  use ModalTrait;
  use ModuleTrait;
  use OverrideTrait;
  use ParagraphsTrait;
  use PathTrait;
  use QueueTrait;
  use RedirectTrait;
  use ResponseTrait;
  use ResponsiveTrait;
  use RestTrait;
  use SearchApiTrait;
  use StateTrait;
  use TableTrait;
  use TaxonomyTrait;
  use TestmodeTrait;
  use UserTrait;
  use WaitTrait;
  use WatchdogTrait;
  use XmlTrait;

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
   * Assert that an element resolves to a computed style value.
   *
   * Asserting on markup alone cannot tell whether a rule reached the element:
   * a class can be present while the declaration it carries never applies.
   *
   * @code
   * Then the element ".admin-toolbar" should have the computed style "border-inline-start-width" of "8px"
   * @endcode
   *
   * @javascript
   */
  #[Then('the element :selector should have the computed style :property of :value')]
  public function elementAssertComputedStyle(string $selector, string $property, string $value): void {
    $script = <<<JS
      return window.getComputedStyle({{ELEMENT}}).getPropertyValue("{$property}");
JS;
    $actual = trim((string) $this->elementExecuteJs($selector, $script));

    if ($actual !== $value) {
      throw new \RuntimeException(sprintf('Expected element "%s" to have a computed "%s" of "%s", but it is "%s".', $selector, $property, $value, $actual));
    }
  }

  /**
   * Assert that an image renders at the aspect ratio of its source.
   *
   * A box whose ratio differs from the source either stretches the image or,
   * for a vector, letterboxes the artwork away from the box edges.
   *
   * @code
   * Then the image ".ct-header__logo .ct-logo__image--desktop" should be rendered at its natural aspect ratio
   * @endcode
   *
   * @javascript
   */
  #[Then('the image :selector should be rendered at its natural aspect ratio')]
  public function imageAssertNaturalAspectRatio(string $selector): void {
    $script = <<<JS
      var box = {{ELEMENT}}.getBoundingClientRect();
      return [box.width, box.height, {{ELEMENT}}.naturalWidth, {{ELEMENT}}.naturalHeight];
JS;
    [$width, $height, $natural_width, $natural_height] = $this->elementExecuteJs($selector, $script);

    if ($width <= 0 || $height <= 0) {
      throw new \RuntimeException(sprintf('The image "%s" is not displayed.', $selector));
    }

    if ($natural_width <= 0 || $natural_height <= 0) {
      throw new \RuntimeException(sprintf('The image "%s" has not loaded.', $selector));
    }

    $ratio = $width / $height;
    $natural_ratio = $natural_width / $natural_height;

    if (abs($ratio - $natural_ratio) / $natural_ratio > 0.02) {
      throw new \RuntimeException(sprintf('Expected image "%s" to render at its natural aspect ratio of %.2f, but its %dx%d box has a ratio of %.2f.', $selector, $natural_ratio, $width, $height, $ratio));
    }
  }

  /**
   * Assert that an element starts at the left edge of another element.
   *
   * @code
   * Then the element ".ct-header__logo img" should start at the left edge of the element ".ct-header__middle .container"
   * @endcode
   *
   * @javascript
   */
  #[Then('the element :selector should start at the left edge of the element :reference')]
  public function elementAssertStartsAtLeftEdge(string $selector, string $reference): void {
    $reference_js = json_encode($reference, JSON_UNESCAPED_SLASHES);
    $script = <<<JS
      var reference = document.querySelector({$reference_js});
      if (!reference) {
        throw new Error('Element with selector ' + {$reference_js} + ' not found.');
      }
      return [{{ELEMENT}}.getBoundingClientRect().left, reference.getBoundingClientRect().left];
JS;
    [$left, $reference_left] = $this->elementExecuteJs($selector, $script);

    if (abs($left - $reference_left) > 1) {
      throw new \RuntimeException(sprintf('Expected element "%s" to start at the left edge of "%s" (x=%d), but it starts at x=%d.', $selector, $reference, $reference_left, $left));
    }
  }

  /**
   * Assert that the displayed child elements of an element share 1 row.
   *
   * @code
   * Then the child elements of ".ct-header .ct-navigation__menu" should be on a single row
   * @endcode
   *
   * @javascript
   */
  #[Then('the child elements of :selector should be on a single row')]
  public function elementAssertChildrenOnSingleRow(string $selector): void {
    $script = <<<JS
      return Array.prototype.filter.call({{ELEMENT}}.children, function (child) {
        return child.getBoundingClientRect().height > 0;
      }).map(function (child) {
        return Math.round(child.getBoundingClientRect().top);
      });
JS;
    $tops = $this->elementExecuteJs($selector, $script);

    if (empty($tops)) {
      throw new \RuntimeException(sprintf('The element "%s" has no displayed child elements.', $selector));
    }

    $rows = array_unique($tops);

    if (count($rows) > 1) {
      throw new \RuntimeException(sprintf('Expected the child elements of "%s" to be on a single row, but they start at %d heights: %s.', $selector, count($rows), implode(', ', $rows)));
    }
  }

  /**
   * Assert that an element paints a fully opaque background colour.
   *
   * @code
   * Then the element ".ct-header__middle" should have an opaque background
   * @endcode
   *
   * @javascript
   */
  #[Then('the element :selector should have an opaque background')]
  public function elementAssertOpaqueBackground(string $selector): void {
    $script = <<<JS
      return window.getComputedStyle({{ELEMENT}}).getPropertyValue('background-color');
JS;
    $color = trim((string) $this->elementExecuteJs($selector, $script));

    // Computed colours carry alpha as 'rgba(r, g, b, a)' or as 'color(... / a)'.
    $alpha = 1.0;

    if ($color === 'transparent') {
      $alpha = 0.0;
    }
    elseif (preg_match('/\/\s*([\d.]+)(%?)\s*\)$/', $color, $matches) || preg_match('/^rgba\(.*,\s*([\d.]+)(%?)\s*\)$/', $color, $matches)) {
      $alpha = $matches[2] === '%' ? (float) $matches[1] / 100 : (float) $matches[1];
    }

    if ($alpha < 1) {
      throw new \RuntimeException(sprintf('Expected element "%s" to have an opaque background, but its background colour is "%s".', $selector, $color));
    }
  }

}
