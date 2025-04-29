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

}
