# Browser errors

Errors in an editor's browser reach the site log, so you can debug a page on a machine where the browser's developer tools are blocked. This page covers what gets logged, how to read it and the limits on each report.

## What is logged

On administration pages, logged-in users load `js/do_base.client_error_log.js` from `do_base`. It reports 2 kinds of error:

- Every uncaught error. This includes the `Drupal.AjaxError` that core throws when an Ajax request fails, which is the error behind the "Oops, something went wrong" message.
- Every unhandled promise rejection.

Each report becomes 1 log entry of type `javascript` at severity Error. The entry holds these fields:

| Field | Value |
|---|---|
| Page | The URL the error happened on |
| Error | The error name and message |
| Source | The file, line and column |
| Stack | The stack trace, when the browser provides one |
| User agent | The browser that sent the report |

For an Ajax failure, the message holds the HTTP status code, the request path, the status text and the response body. A 500 status points at the server. A response body that isn't Drupal's JSON means something between the browser and Drupal replaced the response.

Front-end pages and anonymous visitors load no reporter.

## Read the log

Open **Reports > Recent log messages** and filter by the `javascript` type. To read the entries from the command line, run:

```bash
ahoy drush watchdog:show --type=javascript --extended
```

## Limits

- A page sends at most 10 reports, so an error thrown in a loop can't flood the log.
- Each user can send 50 reports per hour. The endpoint answers report 51 with a 429 response and logs nothing.
- The message keeps its first 4000 characters, and every other field keeps its first 1000.
- The endpoint, `POST /client-error-log`, accepts JSON from logged-in users only. It answers a report with no message with a 400 response.
- Each report carries the session's CSRF token in an `X-CSRF-Token` header, which the script reads from `/session/token`. Another site can't read that token, so it can't post reports for a logged-in user.
- Core catches Ajax command failures itself, such as a stylesheet that fails to load, and writes them only to the browser console. Those failures don't reach the log.

## Related

- [Content security policy](csp.md): violation reports reach the same log through `/report-csp-violation`
