# Security headers

This page lists the security headers every Drupal response carries, which module sends each one and how to change them. It also covers the optional login path rename and the DNS record that sits outside this repository. The content security policy has its own page: [Content security policy](csp.md).

## Which module sends which header

3 modules send headers, and each header has exactly 1 source:

| Header | Value | Sent by |
|---|---|---|
| `Content-Security-Policy` | See [Content security policy](csp.md) | `csp` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | `seckit` |
| `X-Frame-Options` | `DENY` | `seckit` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | `seckit` |
| `Expect-CT` | `max-age=86400, enforce` | `seckit` |
| `From-Origin` | `same` | `seckit` |
| `X-XSS-Protection` | `0` | `seckit` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), fullscreen=(self "https://www.youtube.com")` | `http_response_headers` |
| `Cross-Origin-Opener-Policy` | `same-origin` | `http_response_headers` |
| `Cross-Origin-Resource-Policy` | `same-origin` | `http_response_headers` |
| `X-Permitted-Cross-Domain-Policies` | `none` | `http_response_headers` |

`seckit` settings live in `config/default/seckit.settings.yml`. Each `http_response_headers` header is a config entity in `config/default/http_response_headers.response_header.*.yml`, editable at **Configuration > System > HTTP Response Headers**.

`X-Frame-Options: DENY` repeats the policy's `frame-ancestors 'none'` for browsers that predate that directive. Change both together, or the 2 headers disagree about framing.

`Permissions-Policy` allows fullscreen for `https://www.youtube.com`, because embedded videos need it. `frame-src` in the policy already trusts the same host. `seckit` can only send the obsolete `Feature-Policy` header, so its feature policy is off.

## An empty value removes the header

`http_response_headers` removes a header from the response when its entity has an empty value. The `x_generator` entity uses this to strip the `X-Generator` header core adds.

Enabling the module installs its own sample headers, including an empty `Content-Security-Policy` that strips the policy `csp` sends. Only the entities in `config/default` belong on this site, so delete any others before exporting.

## The generator meta tag

Core adds `<meta name="Generator" content="Drupal 11 ...">` to every page. `PageAttachmentsHook::alter()` in `do_base` removes it, so pages don't name the platform or its version.

## Rename the login path

`/user/login` answers every visitor, which makes it a target for password guessing. Set the `DRUPAL_USER_PATH` environment variable to move every `/user` route to a path of your choice:

| `DRUPAL_USER_PATH` | Login page |
|---|---|
| Unset or empty | `/user/login` |
| `staff` | `/staff/login`, and `/user/login` returns 404 |

`web/sites/default/includes/modules/settings.rename_admin_paths.php` reads the variable and enables `rename_admin_paths`. The repository is public, so the value lives only in the environment.

To rename the path on an environment:

1. Set `DRUPAL_USER_PATH` on that Lagoon environment.
2. Deploy, or run `drush cr`, so the router rebuilds with the new path.
3. Confirm that `/user/login` returns 404 and that the new path shows the login form.

Password reset emails link to the new path, because Drupal builds them from routes.

## Records outside this repository

The `drevops.com` zone in Cloudflare holds the DNS. A CAA record limits which certificate authorities may issue certificates for the domain. Add a CAA record in Cloudflare for each authority the site uses:

| Record | Needed for |
|---|---|
| `0 issue "letsencrypt.org"` | The Lagoon origin certificate |
| The authorities Cloudflare lists for Universal SSL | The edge certificate |

Cloudflare adds the Universal SSL authorities itself after the zone has any CAA record. Leaving out `letsencrypt.org` stops the origin certificate from renewing.

## Verifying a change

```bash
ahoy test-bdd -- --tags=@seckit
ahoy test-bdd -- --tags=@csp
ahoy test-unit -- --filter=SwitchableSettingsTest
```

Then compare the live headers with the table in [Which module sends which header](#which-module-sends-which-header):

```bash
curl -sI https://www.drevops.com/
```

## Related

- [Content security policy](csp.md) - the policy and its nonce
- [Deployment](deployment.md) - the Lagoon environments the variable is set on
