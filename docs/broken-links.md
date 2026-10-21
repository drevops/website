# Broken links

The site checks every link placed in content once a day and lists the results in a report. This page covers what gets checked, when the checks run and how to read the report. The [Link checker](https://www.drupal.org/project/linkchecker) module does the work. Its configuration lives in `linkchecker.settings` and in the settings of each scanned field.

## What gets checked

The module scans a field only when **Scan broken links** is selected in the field's settings. Every rich-text field (`text`, `text_long`, `text_with_summary`) and every link field in `config/default/field.field.*.yml` has it selected. Each uses the extractor that matches its field type:

| Field type | Extractor | What it records |
|---|---|---|
| Rich text | HTML extractor (`html_link_extractor`) | The `href` of every `<a>` and `<area>` tag |
| Link | Link extractor (`link_link_extractor`) | The link's URI |

To scan a rich-text or link field you add:

1. On the field's settings form, select **Scan broken links** under **Link checker settings**.
2. Save the form.
3. Export the configuration:

   ```bash
   ahoy drush cex
   ```

`linkchecker.settings` keeps the module defaults except for 2 settings. The defaults mean:

- Links to `example.com`, `example.net` and `example.org` are skipped.
- Unpublished content is scanned too.
- The checker records a redirect as the answer, so a `301` counts as a failure until you update the link to its new address.
- Answers of `200`, `206`, `302`, `304`, `401` and `403` count as working.
- The automatic actions stay off: content stays published after a `404`, and links stay as written after a `301`.

The 2 settings that differ:

| Setting | Value | Effect |
|---|---|---|
| `check.interval` | `86400` | Each link is checked again 1 day after its last check |
| `check_links_types` | `0` | Internal links are checked as well as external ones |

## When the checks run

The module records the links in an entity each time the entity is saved. Each cron run also records links from 50 entities that have never been scanned. On Lagoon, cron runs every 15 minutes, so content that existed before the first deployment gets scanned at 200 entities an hour.

Each cron run then queues every link that was never checked or was last checked more than a day ago. It works through the queue for up to 240 seconds, checking 8 links at a time.

## The host cron uses

The module stores each internal link as an absolute URL on the host of the request that recorded it. Under cron, that host is Drush's URI. On Lagoon, Drush takes its URI from `LAGOON_ROUTE`, which on `main` is `https://drevops.com`. That host redirects to `www.drevops.com`, so the checker would count every internal link recorded by cron as a failed `301`.

`.lagoon.yml` passes the canonical host to each cron job with `--uri`:

| Environment | Host |
|---|---|
| `main` | `https://www.drevops.com` |
| `develop` | `https://dev.drevops.com` |

[XML sitemap](sitemap.md) sets the same host for the same reason.

## The patch for unreachable hosts

Link checker 2.1.0 passes Guzzle's `ConnectException` to a handler typed for `RequestException`. When a link's host fails to resolve or refuses the connection, the check stops with a `TypeError` and the link stays unchecked.

`composer.json` applies the merge request from [issue 3396666](https://www.drupal.org/project/linkchecker/issues/3396666), which widens the handler to `TransferException`. The checker then records such a link with status code `502` and the connection error. The merge request is unmerged, so the diff lives in `patches/linkchecker-guzzle7-connect-exception.patch`, where it changes only with a commit. Remove the patch after a release includes the fix.

## Read the report

The report at **Reports > Broken links** (`/admin/reports/linkchecker`) lists every recorded link. Each row shows the status code, error, fail count, last check and the page that holds the link. A link inside a paragraph shows the page that holds the paragraph.

To list the links that need fixing, set **Result** to **Failed** and click **Apply**. **Failed** also lists the `302`, `304`, `401` and `403` answers the checker counts as working, with a **Fail count** of 0.

The report needs the **Access broken links report** permission, which only administrators have. The settings are at **Configuration > Content authoring > Link checker** (`/admin/config/content/linkchecker`). Export the configuration after changing them.

## Run the checks by hand

To scan all content for links again, run:

```bash
ahoy drush linkchecker:analyze
```

To check every link that was never checked or was last checked more than a day ago, run:

```bash
ahoy drush linkchecker:check
```

## Verify a change

Run the Behat feature:

```bash
ahoy test-bdd -- tests/behat/features/broken_links.feature
```

## Related

- [XML sitemap](sitemap.md) - the other cron task that needs the canonical host
