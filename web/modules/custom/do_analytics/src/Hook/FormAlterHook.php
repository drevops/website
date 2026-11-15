<?php

declare(strict_types=1);

namespace Drupal\do_analytics\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\do_analytics\AnalyticsAttributes;

/**
 * Names each form for the analytics tracker.
 */
final class FormAlterHook {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $webform_id = $form['#webform_id'] ?? NULL;

    // A webform's form ID includes the entity it is placed on, so the same
    // webform would report a different ID on every page.
    $form['#attributes'][AnalyticsAttributes::FORM] = is_string($webform_id) && $webform_id !== '' ? 'webform:' . $webform_id : $form_id;
  }

}
