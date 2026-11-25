<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Kernel;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\do_base\Controller\ClientErrorLogController;
use Drupal\do_base\Hook\ClientErrorLogHook;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

/**
 * Tests that browser errors on administration pages reach the site log.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class ClientErrorLogTest extends DoBaseKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'dblog',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('dblog', ['watchdog']);
  }

  /**
   * Tests which pages load the error reporter.
   *
   * @param bool $is_admin_route
   *   Whether the page is an administration page.
   * @param bool $is_logged_in
   *   Whether the visitor is logged in.
   * @param bool $expected
   *   Whether the reporter is expected to be attached.
   */
  #[DataProvider('dataProviderReporterIsAttached')]
  public function testReporterIsAttached(bool $is_admin_route, bool $is_logged_in, bool $expected): void {
    // Prepare.
    $this->setRoute($is_admin_route);

    if ($is_logged_in) {
      $this->setUpCurrentUser();
    }
    else {
      $this->container->get('current_user')->setAccount(new AnonymousUserSession());
    }

    $hook = $this->container->get(ClientErrorLogHook::class);

    if (!$hook instanceof ClientErrorLogHook) {
      throw new \UnexpectedValueException('The hook is expected to be registered as a service.');
    }

    // Act.
    $attachments = [];
    $hook->attach($attachments);

    // Assert.
    $this->assertSame($expected, in_array('do_base/client_error_log', $attachments['#attached']['library'] ?? [], TRUE));
    $this->assertContains('user.roles:authenticated', $attachments['#cache']['contexts']);
    $this->assertContains('route', $attachments['#cache']['contexts']);
  }

  /**
   * Data provider for testReporterIsAttached.
   */
  public static function dataProviderReporterIsAttached(): \Iterator {
    yield 'logged-in user on an administration page' => [TRUE, TRUE, TRUE];
    yield 'anonymous visitor on an administration page' => [TRUE, FALSE, FALSE];
    yield 'logged-in user on a front-end page' => [FALSE, TRUE, FALSE];
    yield 'anonymous visitor on a front-end page' => [FALSE, FALSE, FALSE];
  }

  /**
   * Tests that a report is logged with every field it carries.
   */
  public function testReportIsLogged(): void {
    // Prepare.
    $request = $this->createReport([
      'kind' => 'error',
      'name' => 'AjaxError',
      'message' => "An AJAX HTTP error occurred.\nHTTP Result Code: 500\nResponseText: [TEST] Response",
      'stack' => 'AjaxError at ajax.js:1:1',
      'source' => 'https://example.com/core/misc/ajax.js',
      'line' => 1224,
      'column' => 11,
      'url' => 'https://example.com/media/add/package',
    ]);

    // Act.
    $response = $this->controller()->log($request);

    // Assert.
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $entries = $this->logEntries();
    $this->assertCount(1, $entries);
    $this->assertSame([
      '@kind' => 'error',
      '@url' => 'https://example.com/media/add/package',
      '@name' => 'AjaxError',
      '@message' => "An AJAX HTTP error occurred.\nHTTP Result Code: 500\nResponseText: [TEST] Response",
      '@source' => 'https://example.com/core/misc/ajax.js',
      '@line' => 1224,
      '@column' => 11,
      '@agent' => '[TEST] Browser/1.0',
      '@stack' => 'AjaxError at ajax.js:1:1',
    ], $entries[0]);
  }

  /**
   * Tests that a report with only a message is logged with safe defaults.
   */
  public function testMinimalReportIsLogged(): void {
    // Prepare.
    $request = $this->createReport(['message' => '[TEST] Script error.', 'line' => 'x', 'stack' => ['not', 'text']]);

    // Act.
    $response = $this->controller()->log($request);

    // Assert.
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $entries = $this->logEntries();
    $this->assertCount(1, $entries);
    $this->assertSame('Error', $entries[0]['@name']);
    $this->assertSame(0, $entries[0]['@line']);
    $this->assertSame('', $entries[0]['@stack']);
  }

  /**
   * Tests that long values are cut to the length the log keeps.
   */
  public function testLongValuesAreTruncated(): void {
    // Prepare.
    $request = $this->createReport(['message' => str_repeat('m', 5000), 'url' => str_repeat('u', 2000)]);

    // Act.
    $this->controller()->log($request);

    // Assert.
    $entries = $this->logEntries();
    $this->assertSame(4000, mb_strlen($entries[0]['@message']));
    $this->assertSame(1000, mb_strlen($entries[0]['@url']));
  }

  /**
   * Tests that a body that is not a report is refused and nothing is logged.
   *
   * @param string $body
   *   The raw request body.
   */
  #[DataProvider('dataProviderInvalidReportIsRefused')]
  public function testInvalidReportIsRefused(string $body): void {
    // Prepare.
    $request = Request::create('/client-error-log', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);

    // Act.
    $response = $this->controller()->log($request);

    // Assert.
    $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    $this->assertSame([], $this->logEntries());
  }

  /**
   * Data provider for testInvalidReportIsRefused.
   */
  public static function dataProviderInvalidReportIsRefused(): \Iterator {
    yield 'not JSON' => ['[TEST] not json'];
    yield 'empty body' => [''];
    yield 'a JSON string' => ['"[TEST] string"'];
    yield 'no message' => ['{"kind":"error"}'];
    yield 'blank message' => ['{"message":"   "}'];
    yield 'message that is not text' => ['{"message":{"text":"[TEST]"}}'];
  }

  /**
   * Tests that a user's reports are refused once they exceed the hourly limit.
   */
  public function testReportsBeyondTheLimitAreRefused(): void {
    // Prepare.
    $this->setUpCurrentUser();
    $controller = $this->controller();

    for ($i = 1; $i <= 50; $i++) {
      $controller->log($this->createReport(['message' => '[TEST] Report ' . $i]));
    }

    // Act.
    $response = $controller->log($this->createReport(['message' => '[TEST] Report 51']));

    // Assert.
    $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    $this->assertCount(50, $this->logEntries());
  }

  /**
   * Tests that another user is not limited by someone else's reports.
   */
  public function testLimitIsPerUser(): void {
    // Prepare.
    $this->setUpCurrentUser();
    $flood = $this->container->get('flood');

    for ($i = 1; $i <= 50; $i++) {
      $flood->register('do_base.client_error_log', 3600, 'user:' . $this->container->get('current_user')->id());
    }

    $this->setUpCurrentUser();

    // Act.
    $response = $this->controller()->log($this->createReport(['message' => '[TEST] Another user']));

    // Assert.
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    $this->assertCount(1, $this->logEntries());
  }

  /**
   * Tests that only logged-in users can post JSON reports to the endpoint.
   */
  public function testEndpointRequirements(): void {
    // Prepare.
    $this->container->get('router.builder')->rebuild();

    // Act.
    $route = $this->container->get('router.route_provider')->getRouteByName('do_base.client_error_log');

    // Assert.
    $this->assertSame('/client-error-log', $route->getPath());
    $this->assertSame(['POST'], $route->getMethods());
    $this->assertSame('TRUE', $route->getRequirement('_user_is_logged_in'));
    $this->assertSame('json', $route->getRequirement('_content_type_format'));
    $this->assertSame('TRUE', $route->getRequirement('_csrf_request_header_token'));
    $this->assertTrue($route->getOption('no_cache'));
  }

  /**
   * Returns the controller as the container builds it.
   */
  protected function controller(): ClientErrorLogController {
    return ClientErrorLogController::create($this->container);
  }

  /**
   * Builds a report request with the given body fields.
   *
   * @param array<string, mixed> $fields
   *   Fields encoded into the JSON body.
   */
  protected function createReport(array $fields): Request {
    return Request::create('/client-error-log', 'POST', [], [], [], [
      'CONTENT_TYPE' => 'application/json',
      'HTTP_USER_AGENT' => '[TEST] Browser/1.0',
    ], (string) json_encode($fields));
  }

  /**
   * Returns the placeholders of each entry logged to the browser channel.
   *
   * @return array<int, array<string, mixed>>
   *   Placeholder values, oldest entry first.
   */
  protected function logEntries(): array {
    $variables = $this->container->get('database')->select('watchdog', 'w')
      ->fields('w', ['variables'])
      ->condition('type', 'javascript')
      ->orderBy('wid')
      ->execute()
      ->fetchCol();

    return array_map(static fn(string $serialized): array => unserialize($serialized, ['allowed_classes' => FALSE]), $variables);
  }

  /**
   * Puts a request for an administration or front-end route on the stack.
   */
  protected function setRoute(bool $is_admin_route): void {
    $route = new Route('/test');
    $route->setOption('_admin_route', $is_admin_route);

    $request = Request::create('/test');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'do_base.test');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route);

    $requests = $this->container->get('request_stack');
    $current = $requests->getCurrentRequest();
    // The kernel started a session on the request it booted with, and the test
    // base clears that session on the current request as it tears down.
    if ($current !== NULL && $current->hasSession()) {
      $request->setSession($current->getSession());
    }

    $requests->push($request);
  }

}
