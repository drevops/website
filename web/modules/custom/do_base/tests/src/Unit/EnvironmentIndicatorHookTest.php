<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\do_base\Hook\EnvironmentIndicatorHook;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for EnvironmentIndicatorHook.
 */
#[Group('DoBase')]
class EnvironmentIndicatorHookTest extends DoBaseUnitTestBase {

  /**
   * Test that the stylesheet is attached only when the stripe applies.
   */
  #[DataProvider('dataProviderHasSidebarIndicator')]
  public function testPageAttachmentsLibrary(array $modules, array $permissions, ?string $name, bool $expected): void {
    // Prepare.
    $hook = $this->createHook($modules, $permissions, $name);
    $attachments = [];

    // Act.
    $hook->pageAttachments($attachments);

    // Assert.
    $libraries = $attachments['#attached']['library'] ?? [];
    $this->assertSame($expected, in_array('do_base/environment_indicator', $libraries, TRUE));
  }

  /**
   * Data provider for the stripe applicability matrix.
   */
  public static function dataProviderHasSidebarIndicator(): array {
    $both = ['environment_indicator', 'navigation'];
    $all = ['access environment indicator', 'access navigation'];

    return [
      'applies' => [$both, $all, 'local', TRUE],
      'environment_indicator not installed' => [['navigation'], $all, 'local', FALSE],
      'navigation not installed' => [['environment_indicator'], $all, 'local', FALSE],
      'no indicator permission' => [$both, ['access navigation'], 'local', FALSE],
      'no navigation permission' => [$both, ['access environment indicator'], 'local', FALSE],
      'no environment name' => [$both, $all, NULL, FALSE],
      'empty environment name' => [$both, $all, '', FALSE],
    ];
  }

  /**
   * Test that cache metadata is declared whenever the stripe could apply.
   *
   * The metadata cannot be limited to the users who see the stripe: a page
   * cached for a user without the permission would otherwise be reused for
   * one who has it.
   */
  #[DataProvider('dataProviderCacheability')]
  public function testPageAttachmentsCacheability(array $modules, array $permissions, ?string $name, bool $expected): void {
    // Prepare.
    $hook = $this->createHook($modules, $permissions, $name);
    $attachments = [];

    // Act.
    $hook->pageAttachments($attachments);

    // Assert.
    $this->assertSame($expected, in_array('user.permissions', $attachments['#cache']['contexts'] ?? [], TRUE));
    $this->assertSame($expected, in_array('config:environment_indicator.indicator', $attachments['#cache']['tags'] ?? [], TRUE));
    $this->assertSame($expected, in_array('config:environment_indicator.settings', $attachments['#cache']['tags'] ?? [], TRUE));
  }

  /**
   * Data provider for testPageAttachmentsCacheability.
   */
  public static function dataProviderCacheability(): array {
    $both = ['environment_indicator', 'navigation'];
    $all = ['access environment indicator', 'access navigation'];

    return [
      'declared when the stripe applies' => [$both, $all, 'local', TRUE],
      'declared when only the permission is missing' => [$both, ['access navigation'], 'local', TRUE],
      'declared when only the name is missing' => [$both, $all, NULL, TRUE],
      'omitted when environment_indicator is absent' => [['navigation'], $all, 'local', FALSE],
      'omitted when navigation is absent' => [['environment_indicator'], $all, 'local', FALSE],
    ];
  }

  /**
   * Test that the environment colour is exposed on the body element.
   */
  #[DataProvider('dataProviderHasSidebarIndicator')]
  public function testPreprocessHtmlColor(array $modules, array $permissions, ?string $name, bool $expected): void {
    // Prepare.
    $hook = $this->createHook($modules, $permissions, $name);
    $variables = [];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $style = implode(' ', $variables['attributes']['style'] ?? []);
    $this->assertSame($expected, str_contains($style, '--do-environment-indicator-color: #006600;'));
  }

  /**
   * Test that an existing string style attribute is preserved.
   */
  public function testPreprocessHtmlKeepsExistingStringStyle(): void {
    // Prepare.
    $hook = $this->createHook();
    $variables = ['attributes' => ['style' => 'color: red;']];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $this->assertSame(['color: red;', '--do-environment-indicator-color: #006600;'], $variables['attributes']['style']);
  }

  /**
   * Test that the strip is reduced to its attachments.
   */
  public function testPreprocessHtmlStripsIndicatorButKeepsAttachments(): void {
    // Prepare.
    $hook = $this->createHook();
    $attached = ['library' => ['environment_indicator/favicon']];
    $variables = [
      'page_top' => [
        'indicator' => [
          '#type' => 'environment_indicator',
          '#title' => 'local',
          '#attached' => $attached,
        ],
      ],
    ];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $this->assertSame(['#attached' => $attached], $variables['page_top']['indicator']);
  }

  /**
   * Test that cache metadata on the strip is carried over.
   *
   * The module only sets it when environment switcher entities exist, and
   * without it nothing would invalidate the attachments left behind.
   */
  public function testPreprocessHtmlKeepsIndicatorCacheMetadata(): void {
    // Prepare.
    $hook = $this->createHook();
    $attached = ['library' => ['environment_indicator/favicon']];
    $cache = ['tags' => ['config:environment_indicator.settings', 'config:environment_indicator_list']];
    $variables = [
      'page_top' => [
        'indicator' => [
          '#type' => 'environment_indicator',
          '#attached' => $attached,
          '#cache' => $cache,
        ],
      ],
    ];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $this->assertSame(['#attached' => $attached, '#cache' => $cache], $variables['page_top']['indicator']);
  }

  /**
   * Test that the strip is left alone when the stripe does not apply.
   */
  public function testPreprocessHtmlLeavesIndicatorWhenNotApplicable(): void {
    // Prepare.
    $hook = $this->createHook(['environment_indicator', 'navigation'], ['access environment indicator']);
    $indicator = ['#type' => 'environment_indicator', '#title' => 'local'];
    $variables = ['page_top' => ['indicator' => $indicator]];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $this->assertSame($indicator, $variables['page_top']['indicator']);
  }

  /**
   * Test that a missing strip does not break the colour handling.
   */
  public function testPreprocessHtmlWithoutIndicator(): void {
    // Prepare.
    $hook = $this->createHook();
    $variables = [];

    // Act.
    $hook->preprocessHtml($variables);

    // Assert.
    $this->assertArrayNotHasKey('page_top', $variables);
    $this->assertSame(['--do-environment-indicator-color: #006600;'], $variables['attributes']['style']);
  }

  /**
   * Build the hook with mocked dependencies.
   */
  protected function createHook(array $modules = ['environment_indicator', 'navigation'], array $permissions = ['access environment indicator', 'access navigation'], ?string $name = 'local'): EnvironmentIndicatorHook {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(static fn(string $key): ?string => match ($key) {
      'name' => $name,
      'bg_color' => '#006600',
      default => NULL,
    });

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturn($config);

    $current_user = $this->createMock(AccountInterface::class);
    $current_user->method('hasPermission')->willReturnCallback(static fn(string $permission): bool => in_array($permission, $permissions, TRUE));

    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturnCallback(static fn(string $module): bool => in_array($module, $modules, TRUE));

    return new EnvironmentIndicatorHook($config_factory, $current_user, $module_handler);
  }

}
