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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\paragraphs\Entity\Paragraph;

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
   * Attaches a Content paragraph with a formatted rich-text body to a page.
   *
   * The body is supplied verbatim as a PyString so multi-line HTML containing
   * code, commas and semicolons is preserved without the field-table parser
   * escaping, and the text format is set explicitly so the rich-text filters
   * (HTML restriction and Highlight.js) apply on render.
   */
  #[Given('the :bundle :entity_type :title has a :format content paragraph:')]
  public function articleAddContentParagraph(string $bundle, string $entity_type, string $title, string $format, PyStringNode $body): void {
    $parent = $this->paragraphsFindEntity($entity_type, $bundle, 'title', $title);

    if (!$parent instanceof ContentEntityInterface) {
      throw new \RuntimeException(sprintf('The %s %s "%s" was not found.', $bundle, $entity_type, $title));
    }

    $paragraph = Paragraph::create([
      'type' => 'civictheme_content',
      'field_c_p_content' => ['value' => $body->getRaw(), 'format' => $format],
      'field_c_p_theme' => 'dark',
    ]);
    $paragraph->setParentEntity($parent, 'field_c_n_components')->save();

    $components = $parent->get('field_c_n_components')->getValue();
    $components[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $parent->set('field_c_n_components', $components);
    $parent->save();

    static::$paragraphEntities[] = $paragraph;
  }

  /**
   * Assert that content using the reveal hook becomes visible.
   *
   * No template renders the interactions hooks yet, so the reveal behaviour is
   * exercised against representative injected markup.
   */
  #[\Behat\Step\Then('injected reveal content becomes visible')]
  public function assertInjectedRevealBecomesVisible(): void {
    $session = $this->getSession();

    $script = "
      var element = document.createElement('div');
      element.id = 'test-reveal-target';
      element.className = 'component-reveal';
      element.textContent = '[TEST] Reveal target';
      document.body.insertBefore(element, document.body.firstChild);
      Drupal.attachBehaviors(document.body);
    ";
    $session->executeScript($script);

    $revealed = $session->wait(5000, "document.querySelector('#test-reveal-target.visible')");

    if (!$revealed) {
      throw new \Exception('The injected reveal element did not become visible.');
    }
  }

  /**
   * Assert the header stays pinned to the top of the viewport while scrolling.
   */
  #[\Behat\Step\Then('the site header stays pinned to the top of the viewport on scroll')]
  public function assertHeaderStaysAtTop(): void {
    $session = $this->getSession();

    $session->executeScript('window.scrollTo(0, 800);');
    $top = $session->evaluateScript("document.getElementById('siteNav').getBoundingClientRect().top");

    if (abs((float) $top) > 5.0) {
      throw new \Exception(sprintf('The header is not pinned to the top of the viewport after scrolling (top: %s).', $top));
    }
  }

  /**
   * Assert the homepage reflows on a phone without horizontal scrolling.
   *
   * Full-bleed sections such as the hero intentionally span the whole viewport
   * width (including the strip under the vertical scrollbar), and the root
   * clips that overflow so it never becomes a scrollbar. The content is
   * therefore allowed to extend past the client width by the scrollbar width,
   * but anything beyond that is a real layout overflow on small screens.
   */
  #[\Behat\Step\Then('the homepage reflows without horizontal scrolling on a phone')]
  public function assertHomepageNoHorizontalScrollingOnPhone(): void {
    $session = $this->getSession();

    $session->resizeWindow(390, 844, 'current');
    $session->executeScript('Drupal.attachBehaviors(document);');

    $scroll_width = (int) $session->evaluateScript('return document.documentElement.scrollWidth;');
    $client_width = (int) $session->evaluateScript('return document.documentElement.clientWidth;');
    $viewport_width = (int) $session->evaluateScript('return window.innerWidth;');

    $overflow = $scroll_width - $client_width;
    $scrollbar = $viewport_width - $client_width;

    if ($overflow > $scrollbar + 1) {
      throw new \Exception(sprintf('The homepage overflows horizontally by %dpx beyond the full-bleed allowance on a 390px phone viewport.', $overflow - $scrollbar));
    }
  }

  /**
   * Assert the rendered mobile menu opens, locks scrolling and closes on link.
   */
  #[\Behat\Step\Then('the site mobile menu opens, locks scrolling and closes on link activation')]
  public function assertMobileMenuToggles(): void {
    $session = $this->getSession();

    // Narrow the viewport so the toggle is shown and the menu becomes a
    // slide-in panel.
    $session->resizeWindow(390, 844, 'current');

    // Bind the navigation behaviour to the rendered header; re-attaching is a
    // no-op when the initial page load already attached it.
    $session->executeScript('Drupal.attachBehaviors(document);');

    // The behaviour restores whatever overflow the body had before opening, so
    // capture it to assert against rather than assuming an empty string.
    $initial_overflow = (string) $session->evaluateScript("document.body.style.overflow || ''");

    $session->executeScript("document.getElementById('navToggle').click();");
    $opened_js = "document.getElementById('siteNav').classList.contains('is-open')"
      . " && document.body.style.overflow === 'hidden'";
    $opened = $session->wait(3000, $opened_js);

    if (!$opened) {
      throw new \Exception('The mobile menu did not open and lock body scrolling.');
    }

    // Activating a link inside the menu closes it and restores scrolling. The
    // link target is neutralised first so the assertion is not lost to a page
    // navigation.
    $session->executeScript("var link = document.querySelector('#siteNav .component-nav-links a'); link.setAttribute('href', '#'); link.click();");
    $closed_js = "!document.getElementById('siteNav').classList.contains('is-open')"
      . " && document.body.style.overflow === " . json_encode($initial_overflow);
    $closed = $session->wait(3000, $closed_js);

    if (!$closed) {
      throw new \Exception('The mobile menu did not close on link activation.');
    }
  }

  /**
   * Assert that every stat counter animates up to its target value.
   */
  #[\Behat\Step\Then('the stat counters reach their target values')]
  public function assertStatCountersReachTargets(): void {
    $session = $this->getSession();

    $script = "(function () {"
      . " var counters = document.querySelectorAll('.ct-stat-item__count[data-target]');"
      . " if (!counters.length) { return false; }"
      . " for (var i = 0; i < counters.length; i++) {"
      . " if (counters[i].textContent.trim() !== counters[i].getAttribute('data-target')) { return false; }"
      . " }"
      . " return true;"
      . " })()";

    $reached = $session->wait(5000, $script);

    if (!$reached) {
      throw new \Exception('The stat counters did not reach their target values.');
    }
  }

  /**
   * Asserts that an element's computed CSS property resolves to a colour.
   *
   * Works for colour properties (returned by the browser as rgb/rgba) and for
   * CSS custom properties (returned as the authored hex value), normalising
   * both the actual and expected values to an "r,g,b" triplet before comparing.
   *
   * @param string $selector
   *   The CSS selector of the element to inspect.
   * @param string $property
   *   The CSS property or custom property name (e.g. "background-color" or
   *   "--ct-color-dark-background").
   * @param string $expected
   *   The expected colour as a hex or rgb/rgba value.
   */
  #[Then('the computed :property of the element :selector should be :expected')]
  public function assertElementComputedCssColor(string $selector, string $property, string $expected): void {
    $script = sprintf(
      '(function () { var element = document.querySelector(%s); return element ? window.getComputedStyle(element).getPropertyValue(%s) : null; })()',
      json_encode($selector),
      json_encode($property),
    );

    $actual = $this->getSession()->evaluateScript($script);

    if ($actual === NULL) {
      throw new \Exception(sprintf('Element "%s" was not found on the page.', $selector));
    }

    $actual_color = self::normaliseColor((string) $actual);
    $expected_color = self::normaliseColor($expected);

    if ($actual_color !== $expected_color) {
      throw new \Exception(sprintf('Expected computed "%s" of element "%s" to be "%s" (%s), but got "%s" (%s).', $property, $selector, $expected, $expected_color, trim((string) $actual), $actual_color));
    }
  }

  /**
   * Normalises a CSS colour value to an "r,g,b" triplet for comparison.
   *
   * @param string $color
   *   A colour as a 3- or 6-digit hex value or an rgb()/rgba() expression.
   *
   * @return string
   *   The "r,g,b" triplet, or the trimmed input when it cannot be parsed.
   */
  protected static function normaliseColor(string $color): string {
    $color = trim($color);

    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
      $hex = ltrim($color, '#');

      if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
      }

      return sprintf('%d,%d,%d', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    if (preg_match('/rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/', $color, $matches)) {
      return sprintf('%d,%d,%d', $matches[1], $matches[2], $matches[3]);
    }

    return $color;
  }

  /**
   * Create a card group with nested cards on a civictheme_page node.
   *
   * The library's ParagraphsTrait attaches only a single, flat paragraph, so a
   * card group (which references child card paragraphs) needs a dedicated step.
   *
   * @code
   * Given a card group with 2 columns and the following cards on the "[TEST] Page" page:
   *   | type   | title         | description       | icon           |
   *   | number | [TEST] First  | [TEST] First body |                |
   *   | icon   | [TEST] Second | [TEST] Body two   | [TEST] DO Icon |
   * @endcode
   */
  #[Given('a card group with :columns columns and the following cards on the :node_title page:')]
  public function cardGroupCreateWithCards(string $columns, string $node_title, TableNode $cards): void {
    $node = $this->paragraphsFindEntity('node', 'civictheme_page', 'title', $node_title);

    if (!$node instanceof ContentEntityInterface) {
      throw new \RuntimeException(sprintf('The civictheme_page node with the title "%s" was not found.', $node_title));
    }

    $card_refs = [];

    foreach ($cards->getHash() as $row) {
      $values = [
        'type' => 'card',
        'field_c_p_type' => $row['type'],
        'field_c_p_title' => $row['title'],
        'field_c_p_summary' => $row['description'] ?? '',
        'field_c_p_theme' => 'dark',
      ];

      if (!empty($row['icon'])) {
        $icon_ids = \Drupal::entityQuery('media')
          ->accessCheck(FALSE)
          ->condition('bundle', 'civictheme_icon')
          ->condition('name', $row['icon'])
          ->range(0, 1)
          ->execute();

        if (!$icon_ids) {
          throw new \RuntimeException(sprintf('The civictheme_icon media "%s" was not found.', $row['icon']));
        }

        $values['field_c_p_icon'] = ['target_id' => reset($icon_ids)];
      }

      $card = Paragraph::create($values);
      $card->save();
      static::$paragraphEntities[] = $card;

      $card_refs[] = ['target_id' => $card->id(), 'target_revision_id' => $card->getRevisionId()];
    }

    $group = Paragraph::create([
      'type' => 'card_group',
      'field_c_p_list_column_count' => (int) $columns,
      'field_c_p_theme' => 'dark',
      'field_c_p_list_items' => $card_refs,
    ]);
    $group->save();
    static::$paragraphEntities[] = $group;

    $components = $node->get('field_c_n_components')->getValue();
    $components[] = ['target_id' => $group->id(), 'target_revision_id' => $group->getRevisionId()];
    $node->set('field_c_n_components', $components);
    $node->save();
  }

  /**
   * Assert the document does not overflow horizontally at a given width.
   *
   * @code
   * Then the page has no horizontal overflow at 375 pixels wide
   * @endcode
   */
  #[Then('the page has no horizontal overflow at :width pixels wide')]
  public function assertNoHorizontalOverflowAtWidth(string $width): void {
    $session = $this->getSession();
    $session->resizeWindow((int) $width, 900, 'current');

    // Content wider than the viewport is what produces a horizontal scrollbar;
    // the comparison is against the full viewport rather than the scrollbar-
    // reduced client width, with a one-pixel tolerance for sub-pixel rounding.
    $overflow = (int) $session->evaluateScript('Math.ceil(document.documentElement.scrollWidth - window.innerWidth)');

    if ($overflow > 1) {
      throw new \Exception(sprintf('The page overflows horizontally by %dpx at %spx wide.', $overflow, $width));
    }
  }

  /**
   * Assert the assembled components render in the given top-to-bottom order.
   *
   * Each row is one expected marker - "hero:inner", "hero:section",
   * "card-group" or "cta" - matched against the document order of the rendered
   * hero, card group and CTA components.
   */
  #[Then('the page components render in this order:')]
  public function assertComponentsRenderInOrder(TableNode $table): void {
    $expected = array_map(static fn(array $row): string => trim((string) reset($row)), $table->getRows());

    $script = "(function () {\n"
      . "  var root = document.querySelector('article') || document;\n"
      . "  return Array.prototype.slice.call(root.querySelectorAll('.component-hero, .ct-card-group, .ct-cta')).map(function (element) {\n"
      . "    if (element.matches('.component-hero--inner')) { return 'hero:inner'; }\n"
      . "    if (element.matches('.component-hero--section')) { return 'hero:section'; }\n"
      . "    if (element.matches('.ct-card-group')) { return 'card-group'; }\n"
      . "    if (element.matches('.ct-cta')) { return 'cta'; }\n"
      . "    return 'unknown';\n"
      . "  });\n"
      . "})()";

    $actual = (array) $this->getSession()->evaluateScript($script);

    if ($actual !== $expected) {
      throw new \Exception(sprintf('Components render as [%s] but expected [%s].', implode(', ', $actual), implode(', ', $expected)));
    }
  }

  /**
   * Assert the page does not scroll horizontally at the given viewport width.
   *
   * Full-bleed components span the viewport, so the measured scrollbar gutter
   * is allowed for while still catching real overflow that would force a
   * horizontal scrollbar on a phone.
   */
  #[Then('the page reflows without horizontal scrolling at :width pixels wide')]
  public function assertReflowsWithoutHorizontalScrolling(int $width): void {
    $session = $this->getSession();
    $session->resizeWindow($width, 900, 'current');

    $overflow = (int) $session->evaluateScript('document.documentElement.scrollWidth - document.documentElement.clientWidth');
    $scrollbar = (int) $session->evaluateScript('window.innerWidth - document.documentElement.clientWidth');

    if ($overflow > $scrollbar + 1) {
      throw new \Exception(sprintf('The page scrolls horizontally by %dpx at %dpx wide.', $overflow, $width));
    }
  }

}
