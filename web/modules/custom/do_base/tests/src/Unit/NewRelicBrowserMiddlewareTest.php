<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\do_base\NewRelicBrowserTiming;
use Drupal\do_base\StackMiddleware\NewRelicBrowserMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the New Relic browser scripts added to HTML responses.
 */
#[Group('do_base')]
class NewRelicBrowserMiddlewareTest extends DoBaseUnitTestBase {

  /**
   * A policy carrying a script nonce.
   */
  protected const string POLICY = "default-src 'self'; script-src 'self' 'nonce-Ab1_c-D2' 'sha256-x='";

  /**
   * A document with a charset declaration.
   */
  protected const string DOCUMENT = '<!DOCTYPE html><html><head><meta charset="utf-8" /><title>T</title></head><body><p>B</p></body></html>';

  /**
   * Tests that both scripts are added with the policy's nonce.
   */
  public function testScriptsAreAddedWithTheNonce(): void {
    // Prepare.
    $response = $this->htmlResponse(self::DOCUMENT);

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertSame('<!DOCTYPE html><html><head><meta charset="utf-8" /><script nonce="Ab1_c-D2">HEADER</script><title>T</title></head><body><p>B</p><script nonce="Ab1_c-D2">FOOTER</script></body></html>', $result->getContent());
  }

  /**
   * Tests that the header script follows <head> when there is no charset.
   */
  public function testHeaderFollowsHeadWithoutCharset(): void {
    // Prepare.
    $response = $this->htmlResponse('<html><head lang="en"><title>T</title></head><body></body></html>');

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertSame('<html><head lang="en"><script nonce="Ab1_c-D2">HEADER</script><title>T</title></head><body><script nonce="Ab1_c-D2">FOOTER</script></body></html>', $result->getContent());
  }

  /**
   * Tests that the footer script goes before the last closing body tag.
   */
  public function testFooterPrecedesTheLastBodyTag(): void {
    // Prepare.
    $response = $this->htmlResponse('<html><head></head><body><pre>&lt;/body&gt; </body> in text</pre></body></html>');

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertSame('<html><head><script nonce="Ab1_c-D2">HEADER</script></head><body><pre>&lt;/body&gt; </body> in text</pre><script nonce="Ab1_c-D2">FOOTER</script></body></html>', $result->getContent());
  }

  /**
   * Tests that only the header script is added when the footer is empty.
   */
  public function testEmptyFooterIsSkipped(): void {
    // Prepare.
    $response = $this->htmlResponse(self::DOCUMENT);

    // Act.
    $result = $this->middleware($response, footer: '')->handle(Request::create('/'));

    // Assert.
    $this->assertSame('<!DOCTYPE html><html><head><meta charset="utf-8" /><script nonce="Ab1_c-D2">HEADER</script><title>T</title></head><body><p>B</p></body></html>', $result->getContent());
  }

  /**
   * Tests that a content length set inside the middleware is corrected.
   */
  public function testContentLengthIsCorrected(): void {
    // Prepare.
    $response = $this->htmlResponse(self::DOCUMENT);
    $response->headers->set('Content-Length', (string) strlen(self::DOCUMENT));

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertSame((string) strlen((string) $result->getContent()), $result->headers->get('Content-Length'));
  }

  /**
   * Tests that a response without a content length does not gain one.
   */
  public function testContentLengthIsNotAdded(): void {
    // Prepare.
    $response = $this->htmlResponse(self::DOCUMENT);

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertFalse($result->headers->has('Content-Length'));
  }

  /**
   * Tests that a main request turns the agent's own injection off.
   */
  public function testAutoInstrumentationIsDisabled(): void {
    // Prepare.
    $timing = $this->timing();
    $timing->expects($this->once())->method('disableAutoInstrumentation');
    $middleware = new NewRelicBrowserMiddleware($this->kernel($this->htmlResponse(self::DOCUMENT)), $timing);

    // Act.
    $middleware->handle(Request::create('/'));
  }

  /**
   * Tests that responses the scripts cannot or must not go into are unchanged.
   *
   * @param string $content
   *   The response content.
   * @param array<string, string> $headers
   *   The response headers.
   * @param string $header
   *   The header script the agent returns.
   */
  #[DataProvider('dataProviderResponseIsUnchanged')]
  public function testResponseIsUnchanged(string $content, array $headers, string $header = 'HEADER'): void {
    // Prepare.
    $response = new Response($content, 200, $headers);

    // Act.
    $result = $this->middleware($response, header: $header)->handle(Request::create('/'));

    // Assert.
    $this->assertSame($content, $result->getContent());
  }

  /**
   * Data provider for testResponseIsUnchanged.
   */
  public static function dataProviderResponseIsUnchanged(): \Iterator {
    $html = ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Security-Policy' => self::POLICY];

    yield 'not html' => [self::DOCUMENT, ['Content-Type' => 'application/json', 'Content-Security-Policy' => self::POLICY]];
    yield 'no content type' => [self::DOCUMENT, ['Content-Security-Policy' => self::POLICY]];
    yield 'no policy' => [self::DOCUMENT, ['Content-Type' => 'text/html; charset=UTF-8']];
    yield 'policy without a nonce' => [self::DOCUMENT, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Security-Policy' => "script-src 'self'"]];
    yield 'fragment without head' => ['<div>Fragment</div></body>', $html];
    yield 'document without body end' => ['<html><head></head><body>', $html];
    yield 'empty header from the agent' => [self::DOCUMENT, $html, ''];
  }

  /**
   * Tests that a response without readable content is passed through.
   */
  public function testFileResponseIsPassedThrough(): void {
    // Prepare.
    $response = new BinaryFileResponse(__FILE__, 200, ['Content-Type' => 'text/html', 'Content-Security-Policy' => self::POLICY]);

    // Act.
    $result = $this->middleware($response)->handle(Request::create('/'));

    // Assert.
    $this->assertSame($response, $result);
    $this->assertFalse($result->getContent());
  }

  /**
   * Tests that a sub-request is passed through untouched.
   */
  public function testSubRequestIsUnchanged(): void {
    // Prepare.
    $timing = $this->timing();
    $timing->expects($this->never())->method('disableAutoInstrumentation');
    $middleware = new NewRelicBrowserMiddleware($this->kernel($this->htmlResponse(self::DOCUMENT)), $timing);

    // Act.
    $result = $middleware->handle(Request::create('/'), HttpKernelInterface::SUB_REQUEST);

    // Assert.
    $this->assertSame(self::DOCUMENT, $result->getContent());
  }

  /**
   * Tests that nothing happens when the agent is not loaded.
   */
  public function testAgentNotLoaded(): void {
    // Prepare.
    $timing = $this->timing(available: FALSE);
    $timing->expects($this->never())->method('disableAutoInstrumentation');
    $middleware = new NewRelicBrowserMiddleware($this->kernel($this->htmlResponse(self::DOCUMENT)), $timing);

    // Act.
    $result = $middleware->handle(Request::create('/'));

    // Assert.
    $this->assertSame(self::DOCUMENT, $result->getContent());
  }

  /**
   * Builds the middleware around a kernel returning the given response.
   */
  protected function middleware(Response $response, string $header = 'HEADER', string $footer = 'FOOTER'): NewRelicBrowserMiddleware {
    return new NewRelicBrowserMiddleware($this->kernel($response), $this->timing(header: $header, footer: $footer));
  }

  /**
   * Builds a kernel returning the given response.
   */
  protected function kernel(Response $response): HttpKernelInterface {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $kernel->method('handle')->willReturn($response);

    return $kernel;
  }

  /**
   * Builds the agent wrapper returning fixed snippets.
   *
   * @return \Drupal\do_base\NewRelicBrowserTiming&\PHPUnit\Framework\MockObject\MockObject
   *   The mocked wrapper.
   */
  protected function timing(bool $available = TRUE, string $header = 'HEADER', string $footer = 'FOOTER'): NewRelicBrowserTiming {
    $timing = $this->createMock(NewRelicBrowserTiming::class);
    $timing->method('isAvailable')->willReturn($available);
    $timing->method('getHeader')->willReturn($header);
    $timing->method('getFooter')->willReturn($footer);

    return $timing;
  }

  /**
   * Builds an HTML response carrying the policy.
   */
  protected function htmlResponse(string $content): Response {
    return new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Security-Policy' => self::POLICY]);
  }

}
