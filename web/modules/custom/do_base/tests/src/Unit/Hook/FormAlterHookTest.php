<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit\Hook;

use Drupal\do_base\Hook\FormAlterHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for FormAlterHook.
 */
#[CoversClass(FormAlterHook::class)]
#[Group('do_base')]
class FormAlterHookTest extends UnitTestCase {

  /**
   * Tests the reCAPTCHA v3 guard library attachment.
   */
  #[DataProvider('dataProviderAlter')]
  public function testAlter(array $form, bool $expected_attached): void {
    $hook = new FormAlterHook();
    $hook->alter($form);

    $attached = $form['#attached']['library'] ?? [];

    if ($expected_attached) {
      $this->assertContains('do_base/recaptcha_v3_guard', $attached);
    }
    else {
      $this->assertNotContains('do_base/recaptcha_v3_guard', $attached);
    }
  }

  /**
   * Data provider for testAlter().
   */
  public static function dataProviderAlter(): \Iterator {
    yield 'recaptcha v3 attaches guard' => [
      ['captcha' => ['#captcha_type' => 'recaptcha_v3/recaptcha3']],
      TRUE,
    ];

    yield 'different captcha type does not attach' => [
      ['captcha' => ['#captcha_type' => 'image_captcha/Image']],
      FALSE,
    ];

    yield 'no captcha key returns early' => [
      ['name' => ['#type' => 'textfield']],
      FALSE,
    ];

    yield 'captcha without type does not attach' => [
      ['captcha' => []],
      FALSE,
    ];
  }

}
