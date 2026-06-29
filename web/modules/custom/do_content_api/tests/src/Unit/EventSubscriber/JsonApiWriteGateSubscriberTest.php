<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\do_content_api\EventSubscriber\JsonApiWriteGateSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests for JsonApiWriteGateSubscriber.
 */
#[CoversClass(JsonApiWriteGateSubscriber::class)]
#[Group('do_content_api')]
class JsonApiWriteGateSubscriberTest extends UnitTestCase {

  /**
   * Tests that only unauthorised JSON:API writes are denied.
   */
  #[DataProvider('dataProviderOnRequest')]
  public function testOnRequest(string $method, string $route, bool $has_permission, bool $expect_denied): void {
    // Prepare.
    $request = Request::create('/jsonapi/node/civictheme_page', $method);
    $request->attributes->set('_route', $route);

    $event = $this->createMock(RequestEvent::class);
    $event->method('getRequest')->willReturn($request);

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn($has_permission);

    $subscriber = new JsonApiWriteGateSubscriber($account);

    // Assert.
    if ($expect_denied) {
      $this->expectException(AccessDeniedHttpException::class);
    }
    else {
      $this->expectNotToPerformAssertions();
    }

    // Act.
    $subscriber->onRequest($event);
  }

  /**
   * Data provider for testOnRequest().
   */
  public static function dataProviderOnRequest(): \Iterator {
    yield 'safe read on jsonapi is allowed' => ['GET', 'jsonapi.node--civictheme_page.collection', FALSE, FALSE];
    yield 'write on a non-jsonapi route is ignored' => ['POST', 'system.admin_content', FALSE, FALSE];
    yield 'write on jsonapi without permission is denied' => ['POST', 'jsonapi.node--civictheme_page.collection', FALSE, TRUE];
    yield 'write on jsonapi with permission is allowed' => ['POST', 'jsonapi.node--civictheme_page.collection', TRUE, FALSE];
    yield 'delete on jsonapi without permission is denied' => ['DELETE', 'jsonapi.node--civictheme_page.individual', FALSE, TRUE];
  }

}
