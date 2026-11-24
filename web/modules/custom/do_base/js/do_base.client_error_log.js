/**
 * @file
 * Reports uncaught browser errors to the site log.
 *
 * A failed Ajax request throws a Drupal.AjaxError whose message holds the
 * HTTP status, path and response text, so it reaches the log in full.
 * @param {object} Drupal
 * - The Drupal object.
 */

(function doBaseClientErrorLog(Drupal) {
  // Caps the reports a single page sends, so an error thrown in a loop cannot
  // flood the log.
  const limit = 10;
  let sent = 0;

  /**
   * Sends one error to the logging endpoint.
   *
   * @param {string} kind     - The event that surfaced the error.
   * @param {*}      error    - The thrown value, if the event carries one.
   * @param {string} fallback - Message to use when the value has none.
   * @param {object} location - Source, line and column of the error.
   */
  function report(kind, error, fallback, location) {
    if (sent >= limit) {
      return;
    }
    sent += 1;

    const isError = error instanceof Error;
    const body = {
      kind,
      name: isError ? error.name : '',
      message: isError ? error.message : String(error ?? fallback),
      stack: isError && error.stack ? error.stack : '',
      source: location.source || '',
      line: location.line || 0,
      column: location.column || 0,
      url: window.location.href,
    };

    // A failed report is dropped rather than reported, so the logger cannot
    // feed on its own errors.
    fetch(Drupal.url('client-error-log'), {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).catch(function ignoreFailure() {});
  }

  window.addEventListener('error', function reportError(event) {
    report('error', event.error, event.message, {
      source: event.filename,
      line: event.lineno,
      column: event.colno,
    });
  });

  window.addEventListener('unhandledrejection', function reportRejection(event) {
    report('unhandledrejection', event.reason, '', {});
  });
})(Drupal);
