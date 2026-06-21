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
      element.className = 'component-reveal';
      element.textContent = '[TEST] Reveal target';
      document.body.insertBefore(element, document.body.firstChild);
      Drupal.attachBehaviors(document.body);
    ";
    $session->executeScript($script);

    $revealed = $session->wait(5000, "document.querySelector('.component-reveal.visible')");

    if (!$revealed) {
      throw new \Exception('The injected reveal element did not become visible.');
    }
  }

  /**
   * Assert the mobile menu toggles open and closed and locks body scrolling.
   *
   * No template renders the interactions hooks yet, so the mobile navigation
   * behaviour is exercised against representative injected markup.
   */
  #[\Behat\Step\Then('the injected mobile menu toggles open and closed')]
  public function assertInjectedMobileMenuToggles(): void {
    $session = $this->getSession();

    $script = "
      var nav = document.createElement('nav');
      nav.id = 'siteNav';
      nav.className = 'component-nav';
      var toggle = document.createElement('button');
      toggle.id = 'navToggle';
      toggle.setAttribute('aria-expanded', 'false');
      toggle.textContent = 'Menu';
      var menu = document.createElement('div');
      menu.className = 'component-nav-menu';
      var links = document.createElement('div');
      links.className = 'component-nav-links';
      var link = document.createElement('a');
      link.setAttribute('href', '#test-interaction');
      link.textContent = '[TEST] Menu link';
      links.appendChild(link);
      menu.appendChild(links);
      nav.appendChild(toggle);
      nav.appendChild(menu);
      document.body.insertBefore(nav, document.body.firstChild);
      Drupal.attachBehaviors(document.body);
    ";
    $session->executeScript($script);

    $session->executeScript("document.getElementById('navToggle').click();");
    $opened_js = "document.getElementById('siteNav').classList.contains('is-open')"
      . " && document.body.style.overflow === 'hidden'";
    $opened = $session->wait(3000, $opened_js);

    if (!$opened) {
      throw new \Exception('The mobile menu did not open and lock body scrolling.');
    }

    $session->executeScript("document.querySelector('#siteNav .component-nav-links a').click();");
    $closed_js = "!document.getElementById('siteNav').classList.contains('is-open')"
      . " && document.body.style.overflow === ''";
    $closed = $session->wait(3000, $closed_js);

    if (!$closed) {
      throw new \Exception('The mobile menu did not close and restore body scrolling.');
    }
  }

}
