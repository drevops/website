<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Environment indicator hooks for do_base module.
 *
 * Renders the active environment as a coloured stripe along the edge of the
 * Navigation sidebar, the way the module styles Gin's vertical toolbar.
 */
final class EnvironmentIndicatorHook {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    if (!$this->hasModules()) {
      return;
    }

    // The stripe is drawn for some users and not others, and its colour comes
    // from configuration, so the page cannot be cached without both.
    $attachments['#cache']['contexts'][] = 'user.permissions';
    $attachments['#cache']['tags'][] = 'config:environment_indicator.indicator';
    // The favicon attachments kept by preprocessHtml() are built from the
    // module's own settings, which carry the flag that enables them.
    $attachments['#cache']['tags'][] = 'config:environment_indicator.settings';

    if (!$this->hasSidebarIndicator()) {
      return;
    }

    $attachments['#attached']['library'][] = 'do_base/environment_indicator';
  }

  /**
   * Implements hook_preprocess_HOOK() for html templates.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    if (!$this->hasSidebarIndicator()) {
      return;
    }

    $style = $variables['attributes']['style'] ?? [];
    $style = is_array($style) ? $style : [$style];
    $style[] = '--do-environment-indicator-color: ' . $this->activeEnvironment()->get('bg_color') . ';';
    $variables['attributes']['style'] = $style;

    if (!isset($variables['page_top']['indicator'])) {
      return;
    }

    // The full-width strip paints underneath the fixed Navigation chrome, so
    // nothing can see it, yet it still occupies its height in the document
    // flow. Keeping only the attachments drops the strip while preserving the
    // library and settings that recolour the browser favicon. The cache
    // metadata travels with them, so whatever invalidated the strip still
    // invalidates the attachments left behind.
    $variables['page_top']['indicator'] = array_intersect_key($variables['page_top']['indicator'], [
      '#attached' => TRUE,
      '#cache' => TRUE,
    ]);
  }

  /**
   * Whether the environment stripe applies to the current user and site.
   */
  protected function hasSidebarIndicator(): bool {
    if (!$this->hasModules()) {
      return FALSE;
    }

    if (!$this->currentUser->hasPermission('access environment indicator')) {
      return FALSE;
    }

    // Without the sidebar there is nothing to draw the stripe on, and the
    // module's own strip is left alone because nothing covers it.
    if (!$this->currentUser->hasPermission('access navigation')) {
      return FALSE;
    }

    return !empty($this->activeEnvironment()->get('name'));
  }

  /**
   * Whether both modules the stripe builds on are installed.
   */
  protected function hasModules(): bool {
    return $this->moduleHandler->moduleExists('environment_indicator') && $this->moduleHandler->moduleExists('navigation');
  }

  /**
   * The configuration describing the active environment.
   */
  protected function activeEnvironment(): ImmutableConfig {
    return $this->configFactory->get('environment_indicator.indicator');
  }

}
