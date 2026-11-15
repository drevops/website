# Analytics events

This page describes the interaction events the site sends to Google Analytics 4 (GA4) and the context each one carries. Read it before you set up GA4 reports. The `do_analytics` module sends the events.

## How events reach Google Analytics

`do_analytics` attaches 1 script, `js/do_analytics.js`, to every page except admin routes. The script sends each event through `gtag('event', name, params)`. The `google_tag` module loads the GA4 tag `G-LCQ5CZ3H3W` for anonymous visitors only, so events from logged-in users never reach GA4.

The script also dispatches every event as a `doanalytics` DOM event with `{name, params}` in its `detail`. The DOM event fires for every visitor, so you can watch events while logged in (see [Watch events in the browser](#watch-events-in-the-browser)).

## Where the context comes from

Preprocess hooks in `PreprocessHook` add `data-analytics-*` attributes to the root element of each component, with no template overrides. The script reads them from the element an event starts on and from its ancestors.

| Attribute | Added to | Value |
|---|---|---|
| `data-analytics-component` | Every paragraph, and every node rendered in a view mode other than `full` | The paragraph's bundle or the node's view mode, without the `civictheme_` prefix, such as `promo_card` |
| `data-analytics-component-id` | The same elements | `paragraph:<id>` or `node:<id>` |
| `data-analytics-component-title` | Paragraphs with a `field_c_p_title` value, and nodes | The title as plain text, cut to 100 characters |
| `data-analytics-block` | Every placed block | The block's machine name, such as `drevops_footer_menu_1` |
| `data-analytics-region` | Every placed block | The theme region, such as `footer_middle_5` |

`PageAttachmentsHook` adds the page context to `drupalSettings.doAnalytics.page`. On a node page it holds the node bundle and `node:<id>`. On any other page it holds the route name and an empty id.

Some templates don't print the `attributes` of their component, such as the primary navigation block and the pager. A click there takes its component name from the `data-component-id` attribute core adds to every single-directory component.

## Events

The script sends 5 events. Their names differ from the `click`, `scroll` and `form_submit` events GA4's enhanced measurement sends, so a report on 1 event never mixes in the other. The component context is the set of parameters listed under [Parameters](#parameters).

| Event | Sent when | Parameters |
|---|---|---|
| `component_click` | A visitor clicks a link, button, `summary`, checkbox, radio button or element with a `button`, `tab` or `menuitem` role | Component context and click parameters |
| `component_view` | Half of a component is visible, or the component crosses the middle of the viewport, for the first time on the page | Component context |
| `component_form_submit` | A visitor submits a form, before the server validates it | Component context and `form_id` |
| `scroll_depth` | A visitor reaches 25, 50, 75 and 100 percent of a scrollable page, once each | `percent`, `page_type` and `page_id` |
| `content_copy` | A visitor copies selected text | Component context, `element` and `text_length` |

`component_form_submit` counts attempts: a submission that fails validation sends it too.

## Parameters

The script leaves out a parameter that has no value, so a click outside any component sends no `component_id`.

The following table lists the component context, which every event except `scroll_depth` carries:

| Parameter | Value |
|---|---|
| `component` | The `data-analytics-component` of the nearest component. Outside a paragraph or node, the name of the theme component that contains the element, such as `navigation` for a menu link. `page` when no theme component contains it. |
| `component_id` | The `data-analytics-component-id` of the nearest component |
| `component_title` | The `data-analytics-component-title` of the nearest component |
| `component_position` | The component's 1-based position among its sibling components, such as the 3rd card in a list |
| `parent_component` | The `data-analytics-component` of the component that contains this one, such as `manual_list` |
| `parent_component_id` | The `data-analytics-component-id` of that parent |
| `region` | The `data-analytics-region` of the enclosing block |
| `block` | The `data-analytics-block` of the enclosing block |
| `page_type` | The node bundle, or the route name on a page without a node |
| `page_id` | `node:<id>` on a node page |

The following table lists the parameters that belong to 1 or 2 events:

| Parameter | Sent with | Value |
|---|---|---|
| `element` | `component_click`, `content_copy` | The theme component that holds the clicked or copied element, such as `button` or `link`. `content_copy` sends `code` for text copied from `pre` or `code`. |
| `link_text` | `component_click` | The control's `aria-label`, then its visible text, then its `title`, then its text including visually hidden text, then the `alt` of an image inside it. Left out for `phone`. |
| `link_type` | `component_click` | `internal`, `external`, `download`, `email`, `phone`, `anchor`, `lightbox` or `button` |
| `link_url` | `component_click` | The path of an internal link, or the origin and path of an external link. Left out for `email`, `phone` and `button`. |
| `link_domain` | `component_click` | The host name of an external link |
| `form_id` | `component_form_submit` | `webform:<id>` for a webform, such as `webform:contact`, otherwise the Drupal form ID. `FormAlterHook` puts it on the form as `data-analytics-form`. |
| `text_length` | `content_copy` | The number of characters copied |
| `percent` | `scroll_depth` | The scroll threshold reached |

`link_type` is `download` for a link with a `download` attribute or a file extension such as `.pdf`, `.zip` or `.png`. It's `lightbox` for an image link that opens in the PhotoSwipe lightbox.

Every text value is cut to 100 characters, which is the longest event parameter value GA4 accepts.

## What the events leave out

The events carry no form values, no copied text and no phone numbers. An email address in any text value becomes `[email]`. URLs lose their query string, because GA4's terms forbid sending personal information and a query string can carry it.

The admin toolbar and contextual links send no events. To stop clicks, views, form submissions and copies on any other element from sending events, add a `data-analytics-ignore` attribute to it or to an ancestor.

## Set up the events in GA4

GA4 records every parameter, but reports show only the parameters you register as custom dimensions. A standard GA4 property holds 50 event-scoped custom dimensions, and the events use 18 parameters.

1. In GA4, go to **Admin > Data display > Custom definitions** and click **Create custom dimension**.
2. Create an event-scoped dimension for each parameter you want to report on. Set **Event parameter** to the parameter name, such as `component`. Start with `component`, `component_title`, `parent_component`, `region`, `element`, `link_type`, `link_url` and `page_type`.
3. Go to **Admin > Data display > Events** and mark the events that count as conversions as key events, such as `component_form_submit`.

A new custom dimension shows data from the moment you create it, and earlier events stay unreported.

## Watch events in the browser

To print every event a page sends, run the following in the browser console:

```js
document.addEventListener('doanalytics', (event) => console.log(event.detail));
```

Click a component. The console prints the event name and its parameters. The listener misses events sent before you added it, such as views of the components visible on load.

For an anonymous visitor, the same events also sit in `window.dataLayer`, where `gtag` queues them.

## Verify a change

```bash
ahoy test-unit -- --group=do_analytics
ahoy test-bdd -- --tags=@do_analytics
```

The unit tests cover the attributes each hook adds and the page context. Behat builds a page with a list of cards, then checks the rendered attributes and the events a click and a scroll send.

## Related

- [Content security policy](csp.md) - the Google tag sources the policy admits
- [Development](development.md) - the conventions the hooks follow
