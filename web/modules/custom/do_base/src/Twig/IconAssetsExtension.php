<?php

declare(strict_types=1);

namespace Drupal\do_base\Twig;

use Drupal\Core\Theme\ThemeManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension that injects assets_dir global for CivicTheme icon resolution.
 *
 * Sets assets_dir to the active sub-theme's assets directory so that custom
 * icons placed in the sub-theme are resolved by civictheme's icon component.
 */
final class IconAssetsExtension extends AbstractExtension implements GlobalsInterface {

  public function __construct(private readonly ThemeManagerInterface $themeManager) {}

  /**
   * {@inheritdoc}
   */
  public function getGlobals(): array {
    $theme = $this->themeManager->getActiveTheme();
    return ['assets_dir' => '@' . $theme->getName() . '/../assets'];
  }

}
