<?php

declare(strict_types=1);

namespace Drupal\do_base_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Offers 1 button per kind of failure the browser error log captures.
 */
class ClientErrorsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'do_base_test_client_errors';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['server_error'] = [
      '#type' => 'button',
      '#value' => 'Server error',
      '#name' => 'server_error',
      '#ajax' => ['url' => Url::fromRoute('do_base_test.server_error')],
    ];

    $form['not_ajax'] = [
      '#type' => 'button',
      '#value' => 'Not Ajax',
      '#name' => 'not_ajax',
      '#ajax' => ['url' => Url::fromRoute('do_base_test.not_ajax')],
    ];

    foreach (['throw' => 'Throw', 'reject' => 'Reject', 'flood' => 'Flood'] as $action => $label) {
      $form[$action] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $label,
        '#attributes' => ['type' => 'button', 'data-do-base-test' => $action],
      ];
    }

    $form['#attached']['library'][] = 'do_base_test/client_errors';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
