# Content security policy

This document describes **what** the policy admits on this site and why. The module that emits it is [`csp`](https://www.drupal.org/project/csp).

## Where the policy lives

`config/default/csp.settings.yml` holds the enforced directives and their source lists. Two sources are not in that file, because they are per-response and are attached by `PageAttachmentsHook` instead: a nonce and a hash.

## Inline scripts

`script-src` carries no `unsafe-inline`, so an inline `<script>` runs only if the policy names it.

- **The nonce** is generated per response by the `csp` module. Every response asks for one, which is what puts `nonce-{value}` in the directive. Nothing but the module ever writes it.
- **The hash** covers the inline script that core's navigation toolbar renders to set the admin sidebar's state before first paint. That script carries no nonce, so a hash is the only source that admits it. Without it the script is blocked, the console reports the violation, and editors see the sidebar flash from collapsed to expanded on every admin page.

A hash is not a secret: it is a digest of code that ships in Drupal core, it is published in the response header on every request, and it admits exactly one byte-identical script and nothing else. What must stay unpredictable is the nonce, and that never leaves the request it was made for.

## The hash is derived, not recorded

`NavigationScriptHash` reads `core/modules/navigation/layouts/navigation.html.twig`, hashes each inline script it holds, and caches the result. Nothing digest-like is committed, so a core release that edits that script cannot leave a stale value behind.

It logs a warning to the `do_base` channel and returns no hash when it cannot account for a script:

| Warning | Meaning |
|---|---|
| `... is not readable` | The template moved or the module is gone. Check whether core still renders a toolbar script at all. |
| `... holds no inline script this can read` | Core restructured the template. If it now renders the script with a nonce, delete the hash attachment; otherwise adjust the extraction. |
| `... carries Twig syntax` | Core made the script dynamic, so its rendered bytes cannot be known from the template. A nonce is the only remaining option, which means asking core for one. |

`PageAttachmentsTest::testAllowedHashMatchesTheTemplate()` asserts the derivation still reads the template core currently ships, so any of the above fails in continuous integration rather than only appearing in a log.

## Adding an external source

Add the host to the matching directive in `config/default/csp.settings.yml` and export. Prefer self-hosting the asset: the fonts this site serves were moved off `fonts.googleapis.com` for that reason, and the policy has had no Google Fonts source since. See [Front-end performance](performance.md#fonts-are-served-from-this-origin).

## Related

- [Front-end performance](performance.md) - self-hosted fonts, and the editor stylesheets the policy would otherwise block
- [SEO](seo.md) - meta tags, share cards and structured data
