<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Unit\Routing;

use Drupal\do_package\Routing\RouteSubscriber;
use Drupal\Tests\do_base\Traits\ReflectionTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests for RouteSubscriber.
 */
#[CoversClass(RouteSubscriber::class)]
#[Group('do_package')]
class RouteSubscriberTest extends UnitTestCase {

  use ReflectionTrait;

  /**
   * Tests which routes are excluded from caching.
   */
  #[DataProvider('dataProviderAlterRoutes')]
  public function testAlterRoutes(string $route_name, ?bool $expected): void {
    // Prepare.
    $route = new Route('/path');
    $collection = new RouteCollection();
    $collection->add($route_name, $route);

    // Act.
    self::callProtectedMethod(new RouteSubscriber(), 'alterRoutes', [$collection]);

    // Assert.
    $this->assertSame($expected, $route->getOption('no_cache'));
  }

  /**
   * Data provider for testAlterRoutes().
   */
  public static function dataProviderAlterRoutes(): \Iterator {
    yield 'private files' => ['system.files', TRUE];
    yield 'private file path' => ['system.private_file_download', TRUE];
    yield 'temporary files are left alone' => ['system.temporary', NULL];
    yield 'unrelated route is left alone' => ['system.admin', NULL];
  }

  /**
   * Tests that a collection missing the routes is left unchanged.
   */
  public function testMissingRoutesIgnored(): void {
    // Prepare.
    $collection = new RouteCollection();

    // Act.
    self::callProtectedMethod(new RouteSubscriber(), 'alterRoutes', [$collection]);

    // Assert.
    $this->assertCount(0, $collection);
  }

}
