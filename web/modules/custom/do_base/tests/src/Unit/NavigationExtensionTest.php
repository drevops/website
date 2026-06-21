<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\do_base\Twig\NavigationExtension;
use PHPUnit\Framework\Attributes\Group;
use Twig\TwigFunction;

/**
 * Class NavigationExtensionTest.
 *
 * Unit tests for the navigation Twig extension.
 *
 * @package Drupal\do_base\Tests
 */
#[Group('DoBase')]
class NavigationExtensionTest extends DoBaseUnitTestBase {

  /**
   * Tests that the primary navigation function is exposed to Twig.
   */
  public function testGetFunctions(): void {
    $extension = new NavigationExtension($this->createMock(MenuLinkTreeInterface::class));

    $functions = $extension->getFunctions();

    $this->assertCount(1, $functions);
    $this->assertInstanceOf(TwigFunction::class, $functions[0]);
    $this->assertEquals('do_primary_navigation', $functions[0]->getName());
  }

  /**
   * Tests that a populated menu is loaded, transformed and built.
   */
  public function testPrimaryNavigationBuildsTree(): void {
    $built = ['#theme' => 'menu__civictheme_primary_navigation'];

    $menu_tree = $this->createMock(MenuLinkTreeInterface::class);
    $menu_tree->expects($this->once())
      ->method('getCurrentRouteMenuTreeParameters')
      ->with('civictheme-primary-navigation')
      ->willReturn(new MenuTreeParameters());
    $menu_tree->expects($this->once())
      ->method('load')
      ->willReturn(['tree']);
    $menu_tree->expects($this->once())
      ->method('transform')
      ->willReturnArgument(0);
    $menu_tree->expects($this->once())
      ->method('build')
      ->willReturn($built);

    $extension = new NavigationExtension($menu_tree);

    $this->assertSame($built, $extension->primaryNavigation());
  }

  /**
   * Tests that an empty menu short-circuits to an empty render array.
   */
  public function testPrimaryNavigationEmpty(): void {
    $menu_tree = $this->createMock(MenuLinkTreeInterface::class);
    $menu_tree->method('getCurrentRouteMenuTreeParameters')->willReturn(new MenuTreeParameters());
    $menu_tree->method('load')->willReturn([]);
    $menu_tree->expects($this->never())->method('build');

    $extension = new NavigationExtension($menu_tree);

    $this->assertSame([], $extension->primaryNavigation('empty-menu'));
  }

}
