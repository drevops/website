<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit\Routing;

use Drupal\do_content_api\Routing\RouteSubscriber;
use Drupal\Tests\do_base\Traits\ReflectionTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
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
   * Tests that key_auth is added to the subrequests route.
   */
  public function testKeyAuthAdded(): void {
    // Prepare.
    $route = new Route('/subrequests');
    $route->setOption('_auth', ['basic_auth', 'cookie']);
    $collection = new RouteCollection();
    $collection->add('subrequests.front-controller', $route);

    // Act.
    self::callProtectedMethod(new RouteSubscriber(), 'alterRoutes', [$collection]);

    // Assert.
    $this->assertContains('key_auth', $route->getOption('_auth'));
  }

  /**
   * Tests that an existing key_auth provider is not duplicated.
   */
  public function testKeyAuthNotDuplicated(): void {
    $route = new Route('/subrequests');
    $route->setOption('_auth', ['cookie', 'key_auth']);
    $collection = new RouteCollection();
    $collection->add('subrequests.front-controller', $route);

    self::callProtectedMethod(new RouteSubscriber(), 'alterRoutes', [$collection]);

    $this->assertSame(['cookie', 'key_auth'], $route->getOption('_auth'));
  }

  /**
   * Tests that a route without an _auth option still gets key_auth.
   */
  public function testRouteWithoutAuthOption(): void {
    $route = new Route('/subrequests');
    $collection = new RouteCollection();
    $collection->add('subrequests.front-controller', $route);

    self::callProtectedMethod(new RouteSubscriber(), 'alterRoutes', [$collection]);

    $this->assertSame(['key_auth'], $route->getOption('_auth'));
  }

}
