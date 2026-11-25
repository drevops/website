<?php

declare(strict_types=1);

namespace Drupal\do_base;

/**
 * Reads the New Relic browser monitoring snippets from the PHP agent.
 *
 * Every method is a no-op when the agent's extension is not loaded.
 */
class NewRelicBrowserTiming {

  /**
   * Checks whether the agent can produce the browser snippets.
   */
  public function isAvailable(): bool {
    return function_exists('newrelic_get_browser_timing_header');
  }

  /**
   * Stops the agent from injecting the snippets into the response itself.
   */
  public function disableAutoInstrumentation(): void {
    if (function_exists('newrelic_disable_autorum')) {
      newrelic_disable_autorum();
    }
  }

  /**
   * Returns the JavaScript that belongs in the document head.
   *
   * @return string
   *   The script body without tags, or an empty string.
   */
  public function getHeader(): string {
    if (!$this->isAvailable()) {
      return '';
    }

    return newrelic_get_browser_timing_header(FALSE);
  }

  /**
   * Returns the JavaScript that belongs before the closing body tag.
   *
   * The agent returns an empty footer unless the header was read first.
   *
   * @return string
   *   The script body without tags, or an empty string.
   */
  public function getFooter(): string {
    if (!function_exists('newrelic_get_browser_timing_footer')) {
      return '';
    }

    return newrelic_get_browser_timing_footer(FALSE);
  }

}
