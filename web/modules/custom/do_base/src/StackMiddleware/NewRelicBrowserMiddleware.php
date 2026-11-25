<?php

declare(strict_types=1);

namespace Drupal\do_base\StackMiddleware;

use Drupal\Component\Utility\Html;
use Drupal\do_base\NewRelicBrowserTiming;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Adds the New Relic browser monitoring scripts with the CSP nonce.
 *
 * The agent's own injection writes the scripts without a nonce, so the policy
 * blocks them. This middleware wraps the page cache, so a cached page gets the
 * snippets of the request that serves it and the nonce of the policy it is
 * served with.
 */
class NewRelicBrowserMiddleware implements HttpKernelInterface {

  public function __construct(
    protected HttpKernelInterface $httpKernel,
    protected NewRelicBrowserTiming $timing,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    if ($type !== self::MAIN_REQUEST || !$this->timing->isAvailable()) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $this->timing->disableAutoInstrumentation();

    $response = $this->httpKernel->handle($request, $type, $catch);
    $this->addScripts($response);

    return $response;
  }

  /**
   * Adds the header and footer scripts to a full HTML document.
   */
  protected function addScripts(Response $response): void {
    if (!str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
      return;
    }

    $content = $response->getContent();

    if (!is_string($content)) {
      return;
    }

    $head_end = $this->headInsertionOffset($content);
    $body_end = strrpos($content, '</body>');

    if ($head_end === NULL || $body_end === FALSE) {
      return;
    }

    // A script without the policy's nonce is blocked, so nothing is added to a
    // response whose policy has none.
    $nonce = $this->nonce($response);

    if ($nonce === NULL) {
      return;
    }

    $header = $this->timing->getHeader();

    if ($header === '') {
      return;
    }

    $footer = $this->timing->getFooter();

    if ($footer !== '') {
      $content = substr_replace($content, $this->script($footer, $nonce), $body_end, 0);
    }

    $content = substr_replace($content, $this->script($header, $nonce), $head_end, 0);
    $response->setContent($content);

    // Core's content length middleware runs inside this one, so its header
    // counts the content without the scripts.
    if ($response->headers->has('Content-Length')) {
      $response->headers->set('Content-Length', (string) strlen($content));
    }
  }

  /**
   * Finds where the header script goes: after the charset, else after <head>.
   *
   * @return int|null
   *   The byte offset, or NULL when the content has no head element.
   */
  protected function headInsertionOffset(string $content): ?int {
    foreach (['/<meta\s+charset=[^>]*>/i', '/<head\b[^>]*>/i'] as $pattern) {
      if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === 1) {
        return $matches[0][1] + strlen($matches[0][0]);
      }
    }

    return NULL;
  }

  /**
   * Reads the script nonce from the response's enforced policy.
   *
   * @return string|null
   *   The nonce value, or NULL when the policy has none.
   */
  protected function nonce(Response $response): ?string {
    $policy = (string) $response->headers->get('Content-Security-Policy');

    if (preg_match("/'nonce-([A-Za-z0-9+\/_=-]+)'/", $policy, $matches) !== 1) {
      return NULL;
    }

    return $matches[1];
  }

  /**
   * Wraps JavaScript in a script element carrying the nonce.
   */
  protected function script(string $javascript, string $nonce): string {
    return sprintf('<script nonce="%s">%s</script>', Html::escape($nonce), $javascript);
  }

}
