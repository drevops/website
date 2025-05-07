<?php

/**
 * @file
 * Drupal context for Behat testing.
 */

declare(strict_types=1);

use DrevOps\BehatSteps\Drupal\ContentTrait;
use DrevOps\BehatSteps\Drupal\FieldTrait;
use DrevOps\BehatSteps\Drupal\FileTrait;
use DrevOps\BehatSteps\ElementTrait;
use DrevOps\BehatSteps\LinkTrait;
use DrevOps\BehatSteps\Drupal\ParagraphsTrait;
use DrevOps\BehatSteps\PathTrait;
use DrevOps\BehatSteps\ResponseTrait;
use DrevOps\BehatSteps\Drupal\SearchApiTrait;
use DrevOps\BehatSteps\Drupal\TaxonomyTrait;
use DrevOps\BehatSteps\WaitTrait;
use DrevOps\BehatSteps\Drupal\WatchdogTrait;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use ContentTrait;
  use LinkTrait;
  use ElementTrait;
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
   * Visit a revisions page of a type with a specified title.
   *
   * @code
   * When I visit the "article" content revisions page with the title "Test article"
   * @endcode
   *
   * @When I visit the :content_type content revisions page with the title :title
   */
  public function visitRevisionsPageWithTitle(string $content_type, string $title): void {
    $this->contentVisitActionPageWithTitle($content_type, $title, '/revisions');
  }

  /**
   * Select a radio button by its ID.
   *
   * @code
   * When I select the radio button with the id "my-radio-button"
   * @endcode
   *
   * @When I select the radio button with the id :id
   */
  public function assertSelectRadioByIdOnly(string $id = ''): void {
    $radiobutton = $this->getSession()->getPage()->findById($id);

    if ($radiobutton === NULL) {
      throw new \Exception(sprintf('The radio button with id "%s" was not found on the page', $id));
    }

    $value = $radiobutton->getAttribute('value');
    $radiobutton->selectOption($value);
  }

}
