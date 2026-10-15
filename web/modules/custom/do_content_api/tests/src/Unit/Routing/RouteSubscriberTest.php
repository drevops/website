<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit\Routing;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\do_content_api\Routing\RouteSubscriber;
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
#[Group('do_content_api')]
class RouteSubscriberTest extends UnitTestCase {

  use ReflectionTrait;

  /**
   * Tests the authentication providers each route accepts after altering.
   */
  #[DataProvider('dataProviderAlterRoutes')]
  public function testAlterRoutes(string $route_name, ?array $auth, ?array $expected): void {
    // Prepare.
    $route = new Route('/path');

    if ($auth !== NULL) {
      $route->setOption('_auth', $auth);
    }

    $collection = new RouteCollection();
    $collection->add($route_name, $route);

    // Act.
    self::callProtectedMethod($this->createSubscriber(), 'alterRoutes', [$collection]);

    // Assert.
    $this->assertSame($expected, $route->getOption('_auth'));
  }

  /**
   * Data provider for testAlterRoutes().
   */
  public static function dataProviderAlterRoutes(): \Iterator {
    yield 'subrequests keeps its providers' => ['subrequests.front-controller', ['basic_auth', 'cookie'], ['basic_auth', 'cookie', 'key_auth']];
    yield 'subrequests is not duplicated' => ['subrequests.front-controller', ['cookie', 'key_auth'], ['cookie', 'key_auth']];
    yield 'subrequests without an option keeps the global providers' => ['subrequests.front-controller', NULL, ['cookie', 'key_auth']];
    yield 'private files keep the global providers' => ['system.files', NULL, ['cookie', 'key_auth']];
    yield 'private file path keeps the global providers' => ['system.private_file_download', NULL, ['cookie', 'key_auth']];
    yield 'unrelated route is left alone' => ['system.admin', NULL, NULL];
  }

  /**
   * Tests that a collection missing the routes is left unchanged.
   */
  public function testMissingRoutesIgnored(): void {
    // Prepare.
    $collection = new RouteCollection();

    // Act.
    self::callProtectedMethod($this->createSubscriber(), 'alterRoutes', [$collection]);

    // Assert.
    $this->assertCount(0, $collection);
  }

  /**
   * Creates the subscriber with cookie as the only global provider.
   */
  protected function createSubscriber(): RouteSubscriber {
    $provider = $this->createMock(AuthenticationProviderInterface::class);
    $collector = $this->createMock(AuthenticationCollectorInterface::class);
    $collector->method('getSortedProviders')->willReturn(['key_auth' => $provider, 'basic_auth' => $provider, 'cookie' => $provider]);
    $collector->method('isGlobal')->willReturnCallback(static fn(string $provider_id): bool => $provider_id === 'cookie');

    return new RouteSubscriber($collector);
  }

}
