# Preview links

Editors share unpublished content with people who have no account on the site using [`drupal/preview_link`](https://www.drupal.org/project/preview_link). Generating a link produces a tokenised URL that anyone can open, so a reviewer needs neither an account nor a permission.

## Why this module

The editorial workflow keeps `draft` and `needs_review` off the default revision, so a page that is already live can carry a pending draft that no canonical URL will ever render. `preview_link` grants access to the **latest** revision, which covers both a page that has never been published and a draft sitting behind a published one.

The obvious alternative, [`drupal/access_unpublished`](https://www.drupal.org/project/access_unpublished), only unlocks entities whose default revision is unpublished. It cannot reveal a pending draft, which is half the cases here.

## How editors use it

1. Open the content item and choose the **Preview Link** tab (`/node/<nid>/generate-preview-link`).
2. Copy the generated URL and send it on.
3. **Save and regenerate preview link** mints a new token and immediately kills the old URL. **Reset lifetime** restarts the clock without changing the URL.

Opening the link binds its token to the visitor's session, so from that point they can also follow ordinary links into the content.

## What is configured

| Setting | Value | Why |
|---|---|---|
| `enabled_entity_types` | `node`, no bundle list | An empty bundle list means every content type, so a type added later gets preview links without a config change. |
| `expiry_seconds` | `604800` (7 days) | Long enough for a review round, short enough to bound how long unpublished content stays reachable. |
| `multiple_entities` | `true` | A page is assembled from paragraphs and media, which have to travel with the node for the preview to render like the published page will. |
| `display_message` | `subsequent` | Tells the editor a link already exists rather than silently reusing it. |

`generate preview links` is granted to Content Author, Content Approver and Site Administrator. `administer preview link settings` is granted to Site Administrator only. Recipients need no permission at all - the token is the entire credential, which is why neither permission belongs on the anonymous or authenticated role.

## Keeping previews contained

`_do_base_protect_preview_link_page()` acts on any route flagged `_preview_link_route` and does two things the module does not:

- Emits `noindex, nofollow`, because a preview URL is meant to be pasted into mail and chat clients that follow links.
- Triggers the page cache kill switch. Rendering the page issues a session cookie and the response would otherwise be `max-age=900, public`, letting a shared cache serve the content after the link expired or was regenerated.

## Expiry

Expired links are deleted on cron. Expiry is enforced on access, so a link stops working the moment it lapses rather than when cron next runs.

## Related

- [Development agreements](development.md) - function visibility conventions the hook follows
- [Testing](testing.md) - PHPUnit and Behat conventions
