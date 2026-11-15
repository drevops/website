<?php

declare(strict_types=1);

namespace Drupal\Tests\do_analytics\Unit\Hook;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\do_analytics\Hook\PageAttachmentsHook;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Tests the tracker and page context attached to each page.
 */
#[CoversClass(PageAttachmentsHook::class)]
#[Group('do_analytics')]
class PageAttachmentsHookTest extends UnitTestCase {

  /**
   * Tests the Axel library and settings attached to a front-end page.
   *
   * @param mixed $node
   *   The node route parameter, as accepted by hook().
   * @param string|null $route_name
   *   The name of the current route.
   * @param array<string, string> $expected
   *   The expected static event parameters.
   */
  #[DataProvider('dataProviderAttach')]
  public function testAttach(mixed $node, ?string $route_name, array $expected): void {
    $attachments = ['#attached' => ['library' => ['core/drupal']]];

    $this->hook($node, $route_name, FALSE)->attach($attachments);

    $this->assertSame(['core/drupal', 'do_analytics/axel'], $attachments['#attached']['library']);
    $this->assertSame([
      'types' => ['click', 'form_start', 'field_complete', 'form_submit'],
      'properties' => $expected,
    ], $attachments['#attached']['drupalSettings']['axel']);
    $this->assertContains('route', $attachments['#cache']['contexts']);
  }

  /**
   * Data provider for testAttach.
   */
  public static function dataProviderAttach(): \Iterator {
    yield 'node page' => ['node', 'entity.node.canonical', ['page_type' => 'civictheme_page', 'page_id' => 'node:1']];
    yield 'unsaved node' => ['unsaved', 'entity.node.preview', ['page_type' => 'entity.node.preview']];
    yield 'node id only' => ['1', 'entity.node.revision', ['page_type' => 'entity.node.revision']];
    yield 'view page' => [NULL, 'view.blog.page_1', ['page_type' => 'view.blog.page_1']];
    yield 'no route' => [NULL, NULL, []];
  }

  /**
   * Tests that nothing is attached to an admin page.
   */
  public function testAdminRouteIsLeftAlone(): void {
    $attachments = [];

    $this->hook('node', 'entity.node.edit_form', TRUE)->attach($attachments);

    $this->assertSame([], $attachments);
  }

  /**
   * Builds the hook under test.
   *
   * @param mixed $node
   *   The string 'node' for a saved node, 'unsaved' for a new one, otherwise
   *   the raw route parameter.
   * @param string|null $route_name
   *   The name of the current route.
   * @param bool $is_admin
   *   Whether the current route is an admin route.
   */
  protected function hook(mixed $node, ?string $route_name, bool $is_admin): PageAttachmentsHook {
    if ($node === 'node' || $node === 'unsaved') {
      $entity = $this->createMock(NodeInterface::class);
      $entity->method('id')->willReturn($node === 'node' ? 1 : NULL);
      $entity->method('bundle')->willReturn('civictheme_page');
      $node = $entity;
    }

    $route = new Route('/node/{node}');

    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')->with('node')->willReturn($node);
    $route_match->method('getRouteName')->willReturn($route_name);
    $route_match->method('getRouteObject')->willReturn($route);

    $admin_context = $this->createMock(AdminContext::class);
    $admin_context->method('isAdminRoute')->with($route)->willReturn($is_admin);

    return new PageAttachmentsHook($route_match, $admin_context);
  }

}
