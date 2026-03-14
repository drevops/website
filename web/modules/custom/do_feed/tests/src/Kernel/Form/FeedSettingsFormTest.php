<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\do_feed\Form\FeedSettingsForm;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for FeedSettingsForm.
 */
#[CoversClass(FeedSettingsForm::class)]
#[Group('do_feed')]
class FeedSettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'taxonomy',
    'path_alias',
    'views',
    'do_feed',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['do_feed']);
  }

  /**
   * Tests form metadata is correct.
   */
  public function testFormMetadata(): void {
    $form = FeedSettingsForm::create($this->container);

    $this->assertEquals('do_feed_settings_form', $form->getFormId());

    $method = new \ReflectionMethod($form, 'getEditableConfigNames');
    $this->assertEquals(['do_feed.settings'], $method->invoke($form));
  }

  /**
   * Tests form builds with expected field properties and default value.
   */
  public function testBuildFormFieldProperties(): void {
    $form_object = FeedSettingsForm::create($this->container);
    $form = $form_object->buildForm([], new FormState());

    $this->assertArrayHasKey('path_prefix', $form);
    $this->assertEquals('textfield', $form['path_prefix']['#type']);
    $this->assertTrue($form['path_prefix']['#required']);
    $this->assertEquals('feed', $form['path_prefix']['#default_value']);
  }

  /**
   * Tests submitting a value saves to config and reflects in the form.
   */
  #[DataProvider('dataProviderSubmitAndBuildRoundTrip')]
  public function testSubmitAndBuildRoundTrip(string $prefix): void {
    $form_object = FeedSettingsForm::create($this->container);
    $form_state = new FormState();
    $form_state->setValue('path_prefix', $prefix);
    $form = [];
    $form_object->submitForm($form, $form_state);

    // Verify config was saved.
    $this->assertEquals($prefix, $this->config('do_feed.settings')->get('path_prefix'));

    // Verify form reflects saved value.
    $form_object = FeedSettingsForm::create($this->container);
    $built = $form_object->buildForm([], new FormState());
    $this->assertEquals($prefix, $built['path_prefix']['#default_value']);
  }

  /**
   * Data provider for `testSubmitAndBuildRoundTrip()`.
   *
   * @return \Iterator<string, array{string}>
   *   Test cases with path prefix values.
   */
  public static function dataProviderSubmitAndBuildRoundTrip(): \Iterator {
    yield 'short prefix' => ['rss'];
    yield 'hyphenated prefix' => ['custom-feed'];
    yield 'single character' => ['f'];
  }

}
