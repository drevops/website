<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\FunctionalJavascript;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that real browser failures on administration pages reach the log.
 *
 * Each case fails for real in Chrome, through the same path core uses, so the
 * test covers the listener, the CSRF token request and the endpoint together.
 */
#[Group('do_base')]
#[RunTestsInSeparateProcesses]
class ClientErrorLogTest extends DoBaseFunctionalJavascriptTestBase {

  /**
   * Seconds to wait for reports, which the browser sends asynchronously.
   */
  protected const int TIMEOUT = 10;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'dblog', 'do_base', 'do_base_test'];

  /**
   * {@inheritdoc}
   *
   * Every case throws in the browser on purpose.
   */
  protected $failOnJavascriptConsoleErrors = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Core's Ajax error message needs a place to render.
    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests that each kind of failure is logged with what explains it.
   *
   * @param string $button
   *   Label of the button that triggers the failure.
   * @param string $kind
   *   Event expected to have surfaced the error.
   * @param string $name
   *   Error name expected in the entry.
   * @param string[] $expected
   *   Text expected in the logged message.
   */
  #[DataProvider('dataProviderFailureIsLogged')]
  public function testFailureIsLogged(string $button, string $kind, string $name, array $expected): void {
    // Prepare.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('/do-base-test/client-errors');

    // Act.
    $this->getSession()->getPage()->pressButton($button);

    // Assert.
    $entries = $this->waitForEntries(1);
    $this->assertCount(1, $entries);
    $this->assertSame($kind, $entries[0]['@kind']);
    $this->assertSame($name, $entries[0]['@name']);
    $this->assertStringEndsWith('/do-base-test/client-errors', $entries[0]['@url']);
    $this->assertStringContainsString('Chrome', $entries[0]['@agent']);

    foreach ($expected as $text) {
      $this->assertStringContainsString($text, $entries[0]['@message']);
    }

    if ($name === 'AjaxError') {
      $this->releaseFailedAjaxRequest();
    }
  }

  /**
   * Data provider for testFailureIsLogged.
   */
  public static function dataProviderFailureIsLogged(): \Iterator {
    yield 'Ajax request answered with a server error' => [
      'Server error',
      'error',
      'AjaxError',
      ['HTTP Result Code: 500', '/do-base-test/client-errors/server-error', 'ResponseText: [TEST] Server error'],
    ];
    yield 'Ajax request answered with a page instead of commands' => [
      'Not Ajax',
      'error',
      'AjaxError',
      ['HTTP Result Code: 200', '/do-base-test/client-errors/not-ajax', '[TEST] Not an Ajax response'],
    ];
    yield 'uncaught error' => ['Throw', 'error', 'Error', ['[TEST] Uncaught error']];
    yield 'unhandled promise rejection' => ['Reject', 'unhandledrejection', 'TypeError', ['[TEST] Rejected promise']];
  }

  /**
   * Tests that the editor still sees core's message for a failed Ajax request.
   */
  public function testAjaxFailureStillShowsCoreMessage(): void {
    // Prepare.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('/do-base-test/client-errors');

    // Act.
    $this->getSession()->getPage()->pressButton('Server error');

    // Assert.
    $this->assertNotNull($this->assertSession()->waitForText('Oops, something went wrong.'));
    $this->assertCount(1, $this->waitForEntries(1));

    $this->releaseFailedAjaxRequest();
  }

  /**
   * Tests that a page stops reporting after 10 errors.
   */
  public function testReportsPerPageAreCapped(): void {
    // Prepare.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('/do-base-test/client-errors');

    // Act.
    $this->getSession()->getPage()->pressButton('Flood');

    // Assert.
    $this->waitForEntries(10);
    // Waits for any report beyond the cap that is still in flight.
    sleep(2);
    // Reports are sent concurrently, so they reach the log in any order.
    $messages = array_column($this->logEntries(), '@message');
    sort($messages, SORT_NATURAL);
    $this->assertSame(array_map(static fn(int $i): string => '[TEST] Flood ' . $i, range(1, 10)), $messages);
  }

  /**
   * Tests that a post without the session token or JSON body is refused.
   *
   * @param string $token
   *   How the CSRF token is sent: 'session' for the real token, 'none' or a
   *   literal value otherwise.
   * @param string $content_type
   *   Content type of the post.
   * @param int $expected
   *   Expected status code.
   */
  #[DataProvider('dataProviderForgedReportIsRefused')]
  public function testForgedReportIsRefused(string $token, string $content_type, int $expected): void {
    // Prepare.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('/do-base-test/client-errors');

    // Act.
    $this->getSession()->executeScript(sprintf(<<<'JS_WRAP'
    (async () => {
      const headers = {'Content-Type': %s};
      const token = %s;
      if (token === 'session') {
        headers['X-CSRF-Token'] = await (await fetch(Drupal.url('session/token'))).text();
      }
      else if (token !== 'none') {
        headers['X-CSRF-Token'] = token;
      }
      const response = await fetch(Drupal.url('client-error-log'), {method: 'POST', headers, body: JSON.stringify({message: '[TEST] Forged report'})});
      window.doBaseTestStatus = response.status;
    })();
    JS_WRAP, json_encode($content_type), json_encode($token)));

    // Assert.
    $this->assertTrue($this->getSession()->wait(5000, 'window.doBaseTestStatus !== undefined'));
    $this->assertSame($expected, $this->getSession()->evaluateScript('window.doBaseTestStatus'));
    $this->assertSame([], $this->logEntries());
  }

  /**
   * Data provider for testForgedReportIsRefused.
   */
  public static function dataProviderForgedReportIsRefused(): \Iterator {
    yield 'no CSRF token' => ['none', 'application/json', 403];
    yield 'wrong CSRF token' => ['[TEST] not-a-token', 'application/json', 403];
    yield 'not JSON' => ['session', 'text/plain', 415];
  }

  /**
   * Tests that an anonymous visitor's errors are not reported.
   */
  public function testAnonymousErrorsAreNotLogged(): void {
    // Prepare.
    $this->drupalGet('/do-base-test/client-errors');

    // Act.
    $this->getSession()->getPage()->pressButton('Throw');

    // Assert.
    sleep(2);
    $this->assertSame([], $this->logEntries());
  }

  /**
   * Clears the request count a failed Drupal Ajax request leaves behind.
   *
   * Core's test support counts a request as finished on 'ajaxComplete'. Core
   * throws its AjaxError before that event fires, so the count stays at 1 and
   * tear down would report an unfinished request. The logger's own requests
   * settle first, so the count drops to exactly that 1 request.
   */
  protected function releaseFailedAjaxRequest(): void {
    $this->assertTrue($this->getSession()->wait(5000, 'window.drupalActiveXhrCount === 1'), 'Only the failed Ajax request is expected to remain open.');
    $this->getSession()->executeScript('window.drupalActiveXhrCount = 0;');
  }

  /**
   * Waits until at least the given number of entries is logged.
   *
   * @return array<int, array<string, mixed>>
   *   Placeholder values of each entry, oldest first.
   */
  protected function waitForEntries(int $count): array {
    $deadline = time() + static::TIMEOUT;

    do {
      $entries = $this->logEntries();

      if (count($entries) >= $count) {
        return $entries;
      }

      usleep(250000);
    } while (time() < $deadline);

    $this->fail(sprintf('Expected %d browser error entries within %d seconds, found %d.', $count, static::TIMEOUT, count($entries)));
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

}
