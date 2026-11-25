<?php

/**
 * @file
 * New Relic settings.
 */

declare(strict_types=1);

// The agent injects inline browser monitoring scripts without the CSP nonce,
// so the policy blocks them and reports a violation on every page render.
if (function_exists('newrelic_disable_autorum')) {
  newrelic_disable_autorum();
}
