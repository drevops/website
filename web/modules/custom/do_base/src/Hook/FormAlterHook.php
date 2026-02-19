<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form alter hooks for do_base module.
 */
final class FormAlterHook {

  /**
   * Implements hook_form_alter().
   *
   * Attaches the reCAPTCHA v3 guard library to forms that use reCAPTCHA v3
   * to prevent submission before the token is populated.
   */
  #[Hook('form_alter')]
  public function alter(array &$form): void {
    if (!isset($form['captcha'])) {
      return;
    }

    $captcha_type = $form['captcha']['#captcha_type'] ?? '';
    if (str_contains($captcha_type, 'recaptcha_v3')) {
      $form['#attached']['library'][] = 'do_base/recaptcha_v3_guard';
    }
  }

}
