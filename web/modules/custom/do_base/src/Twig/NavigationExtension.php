<?php

declare(strict_types=1);

namespace Drupal\do_base\Twig;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension exposing site navigation as renderable menus.
 *
 * The redesigned header renders the primary menu inline as a flat list rather
 * than through a block, so a dedicated function returns the built menu tree for
 * the template to theme. Limiting the tree to the top level keeps the bar flat,
 * matching the design, while preserving the active trail for highlighting.
 */
final class NavigationExtension extends AbstractExtension {

  public function __construct(protected readonly MenuLinkTreeInterface $menuTree) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('do_primary_navigation', [$this, 'primaryNavigation']),
    ];
  }

  /**
   * Builds a flat, top-level render array for a menu.
   *
   * @param string $menu_name
   *   The menu machine name to render.
   *
   * @return array
   *   A render array for the menu, or an empty array when it has no links.
   */
  public function primaryNavigation(string $menu_name = 'civictheme-primary-navigation'): array {
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMaxDepth(1);
    $parameters->onlyEnabledLinks();

    $tree = $this->menuTree->load($menu_name, $parameters);

    if ($tree === []) {
      return [];
    }

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    return $this->menuTree->build($tree);
  }

}
