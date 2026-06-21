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
use Behat\Step\Then;
use Drupal\DrupalExtension\Context\DrupalContext;

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

}
