<?php

declare(strict_types=1);

namespace Drupal\Tests\do_analytics\Unit\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\do_analytics\Hook\FormAlterHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the name the analytics tracker reports for each form.
 */
#[CoversClass(FormAlterHook::class)]
#[Group('do_analytics')]
class FormAlterHookTest extends UnitTestCase {

  /**
   * Tests the form name added to the form attributes.
   *
   * @param array $form
   *   The form.
   * @param string $form_id
   *   The form ID.
   * @param string $expected
   *   The expected form name.
   */
  #[DataProvider('dataProviderFormAlter')]
  public function testFormAlter(array $form, string $form_id, string $expected): void {
    new FormAlterHook()->formAlter($form, $this->createMock(FormStateInterface::class), $form_id);

    $this->assertSame($expected, $form['#attributes']['data-axel-form']);
  }

  /**
   * Data provider for testFormAlter.
   */
  public static function dataProviderFormAlter(): \Iterator {
    yield 'webform' => [['#webform_id' => 'contact'], 'webform_submission_contact_paragraph_40_add_form', 'webform:contact'];
    yield 'webform with existing attributes' => [['#webform_id' => 'contact', '#attributes' => ['class' => ['ct-webform']]], 'webform_submission_contact_add_form', 'webform:contact'];
    yield 'empty webform id' => [['#webform_id' => ''], 'search_block_form', 'search_block_form'];
    yield 'other form' => [[], 'user_login_form', 'user_login_form'];
  }

  /**
   * Tests that the form keeps the attributes it already has.
   */
  public function testExistingAttributesAreKept(): void {
    $form = ['#attributes' => ['class' => ['ct-webform']]];

    new FormAlterHook()->formAlter($form, $this->createMock(FormStateInterface::class), 'user_login_form');

    $this->assertSame(['class' => ['ct-webform'], 'data-axel-form' => 'user_login_form'], $form['#attributes']);
  }

}
