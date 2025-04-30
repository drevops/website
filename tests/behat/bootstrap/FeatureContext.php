<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\ContentTrait;
use DrevOps\BehatSteps\FieldTrait;
use DrevOps\BehatSteps\FileTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\ParagraphsTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\SearchApiTrait;
use DrevOps\BehatSteps\TaxonomyTrait;
use DrevOps\BehatSteps\WaitTrait;
use DrevOps\BehatSteps\WatchdogTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use LinkTrait;
  use FieldTrait;
  use FileTrait;
  use ParagraphsTrait;
  use PathTrait;
  use ResponseTrait;
  use SearchApiTrait;
  use TaxonomyTrait;
  use WaitTrait;
  use WatchdogTrait;

  /**
   * Disable browser validation for the form for validating errors.
   *
   * @When I disable browser validation for the form with selector :selector
   */
  public function disableFormBrowserValidation(string $selector): void {
    $escapedSelector = addslashes($selector);

    $js = <<<JS
        var form = document.querySelector("{$escapedSelector}");
        if (form) {
            form.setAttribute('novalidate', 'novalidate');
        } else {
            throw new Error("Form with selector {$escapedSelector} not found");
        }
JS;
    $this->getSession()->executeScript($js);
  }

  /**
   * Wait for the page to completely load.
   *
   * Including any BigPipe placeholder replacements.
   *
   * @When I wait for the page to complete loading
   */
  public function waitForPageLoadComplete(): void {
    $this->getSession()->wait(5000, "document.readyState === 'complete'");

    // Wait for any BigPipe placeholders to be replaced.
    $js = "typeof Drupal !== 'undefined' && typeof Drupal.BigPipe !== 'undefined' && Drupal.BigPipe.processedBigPipeResponseCount >= Drupal.BigPipe.totalBigPipeResponseCount";
    $this->getSession()->wait(10000, $js);

    // Wait for any AJAX requests to complete.
    $selector = '.ajax-progress';
    if (isset($this->getDrupalParameter('selectors')['ajax_progress'])) {
      $selector = $this->getDrupalParameter('selectors')['ajax_progress'];
    }
    $this->getSession()->wait(5000, "document.querySelectorAll('" . $selector . "').length === 0");
  }

}
