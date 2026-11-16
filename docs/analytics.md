# Analytics events

This page describes how the site reports clicks and form interactions to Google Analytics 4 (GA4) with [Axel](https://github.com/drevops/axel), and the parameters each event carries. Read it before you set up GA4 reports.

## How it works

Axel reads `data-axel-*` attributes from the page and sends an event when a visitor clicks a link or button, or starts filling in a form. The `do_analytics` module loads Axel and renders those attributes through preprocess hooks, with no template overrides.

The module loads `axel.drupal.gtag.min.js` from jsDelivr on every page except admin routes. The library pins version `0.3.3` with its Subresource Integrity hash, and the `csp` module admits that exact URL in `script-src`. Axel sends each event through `gtag('event', name, params)`. On production, the `google_tag` module loads the GA4 tag `G-LCQ5CZ3H3W` for anonymous visitors only, so events from logged-in users never reach GA4. See [Non-production environments](#non-production-environments) for the other environments.

## Attributes the module renders

| Attribute | Rendered on | Value |
|---|---|---|
| `data-axel-event` | Every paragraph, every node rendered in a view mode other than `full`, and every placed block | `component_click` |
| `data-axel-param-component` | Paragraphs and nodes | The paragraph's bundle or the node's view mode, without the `civictheme_` prefix, such as `promo_card` |
| `data-axel-param-component-id` | Paragraphs and nodes | `paragraph:<id>` or `node:<id>` |
| `data-axel-param-component-title` | Paragraphs with a `field_c_p_title` field, reference paragraphs and nodes | The title as plain text, cut to 100 characters, with email addresses replaced by `[email]`. A reference paragraph, such as `promo_card_ref`, uses the title of the content it references. A paragraph with an empty `field_c_p_title`, such as a `manual_list` with its heading in the intro, uses the first heading in `field_c_p_content`. |
| `data-axel-param-block` | Placed blocks | The block's machine name, such as `drevops_footer_menu_1` |
| `data-axel-param-region` | Placed blocks | The theme region, such as `footer_middle_5` |
| `data-axel-form` | Every form | `webform:<id>` for a webform, such as `webform:contact`, otherwise the Drupal form ID |
| `data-axel-form-submitted` | Webform confirmations rendered on a page or inline | `webform:<id>` |

Axel gathers parameters from the clicked element up through its ancestors, and the closest value of each name wins. A click on a card in the main content therefore sends the card's `component` and the content block's `block` and `region`.

`PageAttachmentsHook` puts the Axel settings in `drupalSettings.axel`. They turn on the `click`, `form_start`, `field_complete` and `form_submit` event types, and add `page_type` and `page_id` to every event. `page_type` is the node bundle, or the route name on a page without a node. `page_id` is `node:<id>` on a node page.

## Events

| Event | Sent when | Parameters |
|---|---|---|
| `component_click` | A visitor clicks a link or button inside an element with `data-axel-event` | `link_text`, `click_type`, `link_url` and the module's parameters |
| `form_start` | Focus first enters a form | `form_name`, `form_id`, `form_destination` and the module's parameters |
| `field_complete` | A field in a form changes | The `form_start` parameters, `field_name` and `field_label` |
| `form_submit` | A `data-axel-form-submitted` confirmation is on the page | `form_name` |

The module's parameters are the `data-axel-param-*` values and the `page_type` and `page_id` settings. The [Axel README](https://github.com/drevops/axel#event-types) describes how Axel derives each parameter. It never sends what a visitor types, strips query strings from URLs and sends `mailto:` and `tel:` links as their scheme only.

A webform sends `form_submit` only when its confirmation type is **Inline** or **Page**, because those render the confirmation the module marks. The contact webform confirms inline and submits with AJAX, so refreshing the confirmation never resubmits the enquiry. A confirmation shown as a status message after a redirect carries no marker and sends no `form_submit`.

To keep an element out of every event, add `data-axel-skip` to it or to an ancestor.

## Set up the events in GA4

GA4 records every parameter, but reports show only the parameters you register as custom dimensions.

1. In GA4, go to **Admin > Data display > Custom definitions** and click **Create custom dimension**.
2. Create an event-scoped dimension for each parameter you want to report on. Set **Event parameter** to the parameter name, such as `component`. Start with `component`, `component_title`, `block`, `region`, `link_text`, `click_type`, `form_name` and `page_type`.
3. Go to **Admin > Data display > Events** and mark the events that count as conversions as key events, such as `form_submit`.

GA4 Enhanced Measurement also sends `form_start` and `form_submit`. Turn off **Form interactions** in the web stream's Enhanced Measurement settings, so each form event counts once.

## Watch events in the browser

Append `#axel-debug` to a page URL. Axel logs every event to the browser console and marks it with `debug_mode`, so GA4 lists it in **Admin > DebugView**.

## Non-production environments

The `ci`, `dev`, `local` and `stage` config splits each hold a partial patch that sets the `G-LCQ5CZ3H3W` tag container's `status` to `false`. Local, CI, dev, stage and pull-request environments send nothing to GA4, so test clicks never reach the production reports. Production activates no split and keeps the container from `config/default`. Axel still runs everywhere and queues its events in `window.dataLayer`, so `#axel-debug` and the Behat steps work as usual.

To see dev and pull-request events in GA4, report them to a separate GA4 property:

1. In GA4, create a property for testing and copy the measurement ID of its web stream, such as `G-ABC123XYZ`.
2. Replace the content of the patch in `config/dev/` with:

   ```yaml
   adding:
     tag_container_ids:
       - G-ABC123XYZ
   removing:
     tag_container_ids:
       - G-LCQ5CZ3H3W
   ```

3. Deploy the change. The next configuration import on a dev or pull-request environment loads the tag with the test ID.

## Update Axel

1. Take the version and the `axel.drupal.gtag.min.js` hash from the notes of the [Axel release](https://github.com/drevops/axel/releases).
2. Replace the URL, the `version` and the `integrity` value in `web/modules/custom/do_analytics/do_analytics.libraries.yml`.
3. Replace the Axel URL in the `script-src` sources of `config/default/csp.settings.yml`.

## Verify a change

```bash
ahoy test-unit -- --group=do_analytics
ahoy test-bdd -- --tags=@do_analytics
```

The unit tests cover the attributes each hook renders and the Axel settings. Behat builds a page with a list of cards, then checks the rendered attributes and the events that clicks and a form focus send.

## Related

- [Content security policy](csp.md) - the script sources the policy admits
- [Development](development.md) - the conventions the hooks follow
