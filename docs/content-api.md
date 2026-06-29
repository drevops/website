# Content authoring API

This site exposes a JSON:API write surface so an external agent (for example a local AI assistant) can create CivicTheme pages - including their components and images - over HTTP, authenticated with an API key. No SSH or drush access is required.

This document is the authoring contract: it describes the endpoints, the content model, and the exact request shapes a client must send. It is intended to be read by both humans and the AI client that calls the API.

## Governance model (read this first)

Content created through the API is **never published automatically**. A human reviews and publishes it.

- **Pages** (`civictheme_page` nodes) are always created as **draft**. The server forces this: even if a request asks for `published`, the page is coerced to `draft`.
- **Images** (`civictheme_image` media) are created **published** - they are assets, invisible until referenced by a published page. The server forces this too, so a client never has to set media moderation state.

So the workflow is: the agent creates a draft page with published images, an editor reviews the draft, and when they publish the page it renders immediately because the images are already live.

## Authentication

Send the API key in the `api-key` request header on every request:

```
api-key: <key>
```

The key belongs to a dedicated, least-privilege service account (`do_content_api_service`) that may only create pages, images, and the supported components - nothing else, and it can never publish.

Retrieve (or regenerate) the key as an administrator at `/user/<uid>/key-auth` for the service account, or have a developer read it from the account. Always send it over HTTPS. On deployed environments the `shield` module may sit in front of the site; the API path must be allow-listed there or the client must also supply the Shield credentials.

## Setup (administrators)

The feature ships as configuration, so it is enabled by a normal deployment:

1. Importing configuration enables `jsonapi` (with writes allowed), `key_auth`, `subrequests`, and the `do_content_api` module, and creates the `do_content_api` service role.
2. The `drush deploy` step runs a deploy hook that creates the `do_content_api_service` account (idempotent - it is skipped if the account already exists). The API key is generated automatically.
3. Retrieve the key at `/user/<uid>/key-auth` and give it to the client.

No keys are committed to the repository; each environment issues its own.

## Endpoints

- **JSON:API base**: `/jsonapi`. Each resource is at `/jsonapi/<entity-type>/<bundle>`, e.g. `/jsonapi/node/civictheme_page`. Use `Content-Type: application/vnd.api+json` and `Accept: application/vnd.api+json`.
- **Subrequests**: `/subrequests?_format=json`. Send one blueprint that creates several entities in a single round-trip (the recommended way to build a page). Use `Content-Type: application/json`.
- **File upload**: `POST /jsonapi/media/civictheme_image/field_c_m_image` with the raw bytes. This is a binary request and cannot be embedded in a blueprint, so upload files first, then reference them.

## Content model

A page is a `civictheme_page` node whose `field_c_n_components` is an **ordered list of paragraphs** (the components). Components reference paragraphs through `entity_reference_revisions`, so every component reference must carry **both** the paragraph id and its `target_revision_id` (see the worked example). Images are never attached directly: the chain is always **component -> media (`civictheme_image`) -> file**, and alt text is mandatory.

### Page fields (`node--civictheme_page`)

| Field | Type | Notes |
| --- | --- | --- |
| `title` | attribute (string) | Required. |
| `moderation_state` | attribute | Always ends up `draft` (server-enforced). |
| `field_c_n_components` | relationship (paragraphs) | Ordered list of component paragraphs. |
| `field_c_n_summary` | attribute (string) | Optional teaser/summary. |

### Supported components (v1)

Every component takes `field_c_p_theme` (`light` | `dark`) and `field_c_p_vertical_spacing` (`none` | `top` | `bottom` | `both`). Rich-text fields use `{ "value": "<p>...</p>", "format": "civictheme_rich_text" }`.

| Paragraph bundle | Purpose | Key fields |
| --- | --- | --- |
| `civictheme_content` | Rich-text block | `field_c_p_content` (rich text) |
| `civictheme_callout` | Callout with action | `field_c_p_title`*, `field_c_p_content`* (rich text), `field_c_p_links`* (link) |
| `civictheme_promo` | Promo with a link | `field_c_p_title`*, `field_c_p_link`* (link), `field_c_p_content` (rich text) |
| `civictheme_campaign` | Image + text + image position | `field_c_p_title`*, `field_c_p_image`* (media), `field_c_p_image_position`* (`left` \| `right`), `field_c_p_content` (rich text) |
| `civictheme_next_step` | Image call-to-action | `field_c_p_title`*, `field_c_p_image` (media), `field_c_p_link` (link) |
| `civictheme_accordion` | Accordion wrapper | `field_c_p_panels`* (-> `civictheme_accordion_panel`) |
| `civictheme_accordion_panel` | One accordion row (child of accordion, not placed directly on the page) | `field_c_p_title`*, `field_c_p_content`* (rich text) |
| `civictheme_manual_list` | Card list wrapper | `field_c_p_list_items`* (-> card paragraphs), `field_c_p_title`, `field_c_p_content` |

Fields marked `*` are required. `civictheme_accordion_panel` and the card paragraphs are children: they are created the same way and referenced from their parent's relationship (`field_c_p_panels` / `field_c_p_list_items`) with the same id + `target_revision_id` shape used for page components.

### Images (`media--civictheme_image`)

1. Upload the bytes: `POST /jsonapi/media/civictheme_image/field_c_m_image` with `Content-Type: application/octet-stream` and `Content-Disposition: file; filename="<name>.png"`. The response is a `file--file` resource - keep its `id` (uuid).
2. Create the media, referencing the file and **always supplying `alt`** (alt text is required):

```json
{
  "data": {
    "type": "media--civictheme_image",
    "attributes": { "name": "Descriptive name" },
    "relationships": {
      "field_c_m_image": {
        "data": { "type": "file--file", "id": "<file-uuid>", "meta": { "alt": "Describe the image" } }
      }
    }
  }
}
```

3. Reference the media's `id` from a component's image field (e.g. `field_c_p_image`).

## Recommended flow: one blueprint per page

After uploading any images and creating their media, send a single subrequests blueprint that creates the component paragraphs and then the page, wiring them together with response tokens. The token `{{<requestId>.body@$.<json-path>}}` is replaced with a value from an earlier sub-response. Crucially, thread each paragraph's `drupal_internal__revision_id` into the page's component relationship `meta.target_revision_id`.

Worked example - a page with a rich-text block and an image campaign component (assuming an image was already uploaded and its media created as `MEDIA_UUID`):

```json
[
  {
    "requestId": "para-text",
    "action": "create",
    "uri": "/jsonapi/paragraph/civictheme_content",
    "headers": { "Content-Type": "application/vnd.api+json", "Accept": "application/vnd.api+json" },
    "body": "{\"data\":{\"type\":\"paragraph--civictheme_content\",\"attributes\":{\"field_c_p_theme\":\"light\",\"field_c_p_vertical_spacing\":\"bottom\",\"field_c_p_content\":{\"value\":\"<p>Hello world.</p>\",\"format\":\"civictheme_rich_text\"}}}}"
  },
  {
    "requestId": "para-campaign",
    "action": "create",
    "uri": "/jsonapi/paragraph/civictheme_campaign",
    "headers": { "Content-Type": "application/vnd.api+json", "Accept": "application/vnd.api+json" },
    "body": "{\"data\":{\"type\":\"paragraph--civictheme_campaign\",\"attributes\":{\"field_c_p_title\":\"Join us\",\"field_c_p_theme\":\"light\",\"field_c_p_vertical_spacing\":\"bottom\",\"field_c_p_image_position\":\"left\"},\"relationships\":{\"field_c_p_image\":{\"data\":{\"type\":\"media--civictheme_image\",\"id\":\"MEDIA_UUID\"}}}}}"
  },
  {
    "requestId": "page",
    "waitFor": ["para-text", "para-campaign"],
    "action": "create",
    "uri": "/jsonapi/node/civictheme_page",
    "headers": { "Content-Type": "application/vnd.api+json", "Accept": "application/vnd.api+json" },
    "body": "{\"data\":{\"type\":\"node--civictheme_page\",\"attributes\":{\"title\":\"My new page\"},\"relationships\":{\"field_c_n_components\":{\"data\":[{\"type\":\"paragraph--civictheme_content\",\"id\":\"{{para-text.body@$.data.id}}\",\"meta\":{\"target_revision_id\":\"{{para-text.body@$.data.attributes.drupal_internal__revision_id}}\"}},{\"type\":\"paragraph--civictheme_campaign\",\"id\":\"{{para-campaign.body@$.data.id}}\",\"meta\":{\"target_revision_id\":\"{{para-campaign.body@$.data.attributes.drupal_internal__revision_id}}\"}}]}}}}"
  }
]
```

The response is HTTP `207` with one entry per `requestId`. Each entry has its own `status` (a `201` means that sub-entity was created) and `body`. The page's `field_c_n_components` in the response lists the linked paragraphs with their resolved `target_revision_id`.

## Gotchas

- **Revision ids are mandatory for components.** A component reference of just `{type, id}` is silently dropped - the page is created without that component. Always include `meta.target_revision_id`.
- **Order tokens correctly.** Use `drupal_internal__revision_id` from each paragraph's creation response. The numeric token is written quoted (`"target_revision_id":"{{...}}"`); the replacer strips the quotes.
- **Alt text is required** on every image.
- **Rich text needs a format**: `civictheme_rich_text`.
- **Do not set page `moderation_state` to `published`** - it is ignored and forced to `draft`. Mark a page ready for review with `needs_review` if desired.
- **Cards (`civictheme_*_card`) are not placed directly on the page** - they live inside a `civictheme_manual_list` via `field_c_p_list_items`.
