# Preview links

Editors share unpublished content with people who have no account on the site using [`drupal/preview_link`](https://www.drupal.org/project/preview_link). Generating a link produces a tokenised URL that anyone can open, so a reviewer needs neither an account nor a permission.

## Why this module

The editorial workflow keeps `draft` and `needs_review` off the default revision, so a page that is already live can carry a pending draft that no canonical URL will ever render. `preview_link` grants access to the **latest** revision, which covers both a page that has never been published and a draft sitting behind a published one.

The obvious alternative, [`drupal/access_unpublished`](https://www.drupal.org/project/access_unpublished), only unlocks entities whose default revision is unpublished. It cannot reveal a pending draft, which is half the cases here.

## How editors use it

1. Open the content item and choose the **Preview Link** tab (`/node/<nid>/generate-preview-link`).
2. Copy the generated URL and send it on.
3. **Save and regenerate preview link** mints a new token and immediately kills the old URL. **Reset lifetime** restarts the clock without changing the URL.

Opening the link binds its token to the visitor's session, so from that point they can follow ordinary links into the content: a URL they could not otherwise see redirects them to its preview, carrying a notice that explains why and offers to drop the token.

## What is configured

| Setting | Value | Why |
|---|---|---|
| `enabled_entity_types` | `node`, no bundle list | An empty bundle list means every content type, so a type added later gets preview links without a config change. |
| `expiry_seconds` | `604800` (7 days) | Long enough for a review round, short enough to bound how long unpublished content stays reachable. |
| `multiple_entities` | `true` | A page is assembled from paragraphs and media, which have to travel with the node for the preview to render like the published page will. |
| `display_message` | `subsequent` | Shows the recipient why they can see the page, and offers to drop the token, but only when they arrive by being redirected from a normal URL. Landing on the preview link itself says nothing, which keeps the first thing they see the content rather than a notice. |

`generate preview links` is granted to Content Author, Content Approver and Site Administrator. `administer preview link settings` is granted to Site Administrator only. Recipients need no permission at all - the token is the entire credential, which is why neither permission belongs on the anonymous or authenticated role.

## Keeping previews contained

Both act on any route flagged `_preview_link_route`, and both cover gaps the module leaves open:

- `_do_base_attach_preview_link_robots()` emits `noindex, nofollow`, because a preview URL is meant to be pasted into mail and chat clients that follow links.
- `PreviewLinkCacheSubscriber` sends `Cache-Control: no-store` and trips the page cache kill switch. Left alone the response is `max-age=900, public`, and merely starting a session would only downgrade it to `private` - enough to bar shared caches, but the recipient's own browser could still surface the content from history once the link had expired or been regenerated.

## Expiry

Expired links are deleted on cron. Expiry is enforced on access, so a link stops working the moment it lapses rather than when cron next runs.

## Related

- [Development agreements](development.md) - function visibility conventions the hook follows
- [Testing](testing.md) - PHPUnit and Behat conventions
