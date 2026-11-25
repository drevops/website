/**
 * @file
 * Reports uncaught browser errors to the site log.
 *
 * A failed Ajax request throws a Drupal.AjaxError whose message holds the HTTP
 * status, path and response text, so it reaches the log in full.
 * @param {object} Drupal  - The Drupal object.
 */

(function doBaseClientErrorLog(Drupal) {
  // Caps the reports a single page sends, so an error thrown in a loop cannot
  // flood the log.
  const limit = 10;
  let sent = 0;
  let token = null;

  // Match the lengths the endpoint keeps, so a long Ajax response body cannot
  // push the request past the 64 KiB a keepalive request may carry.
  const messageLength = 4000;
  const fieldLength = 1000;

  /**
   * Converts a value to a string cut to a maximum length.
   *
   * @param {unknown} value   - The value to convert.
   * @param {number}  length  - Longest string kept.
   * @return {string} The cut string.
   */
  function cut(value, length) {
    return String(value ?? '').slice(0, length);
  }

  /**
   * Sends one error to the logging endpoint.
   *
   * @param {string}  kind      - The event that surfaced the error.
   * @param {unknown} error     - The thrown value, if the event carries one.
   * @param {string}  fallback  - Message to use when the value has none.
   * @param {object}  location  - Source, line and column of the error.
   */
  function report(kind, error, fallback, location) {
    if (sent >= limit) {
      return;
    }
    sent += 1;

    const isError = error instanceof Error;
    const message = isError ? error.message : (error ?? fallback);
    // The endpoint refuses a report without a message, and both an empty
    // Error and a rejection without a reason carry none.
    const body = {
      kind,
      name: isError ? cut(error.name, fieldLength) : '',
      message: cut(message, messageLength) || `${kind} without a message`,
      stack: isError ? cut(error.stack, fieldLength) : '',
      source: cut(location.source, fieldLength),
      line: location.line || 0,
      column: location.column || 0,
      url: cut(window.location.href, fieldLength),
    };

    // A failed report is dropped rather than reported, so the logger cannot
    // feed on its own errors.
    csrfToken()
      .then(function send(token) {
        return fetch(Drupal.url('client-error-log'), {
          method: 'POST',
          credentials: 'same-origin',
          keepalive: true,
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
          body: JSON.stringify(body),
        });
      })
      .catch(function ignoreFailure() {});
  }

  /**
   * Fetches the session's CSRF token once per page.
   *
   * @return {Promise<string>} The token the endpoint requires.
   */
  function csrfToken() {
    if (!token) {
      token = fetch(Drupal.url('session/token'), { credentials: 'same-origin' }).then(function readToken(response) {
        if (!response.ok) {
          throw new Error(`Token request failed with ${response.status}`);
        }

        return response.text();
      });
      // A failed request is retried by the next report.
      token.catch(function resetToken() {
        token = null;
      });
    }

    return token;
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
