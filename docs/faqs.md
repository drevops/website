# FAQs

For common questions and answers, see
[Vortex FAQs](https://www.vortextemplate.com/docs/development/faqs).

## Project-specific FAQs

### Why does the "Publish content" bulk action fail with "No access to execute"?

Every node bundle is under the `civictheme_editorial` content moderation workflow. Core forbids the `Publish content` and `Unpublish content` bulk actions on moderated entities, because publication status is owned by the workflow rather than by the `status` field. The failure is by design and applies to every account, including uid 1.

Use `Publish latest revision`, `Unpublish current revision` or `Archive current revision` instead. These apply a workflow transition and are available to the `civictheme_site_administrator` and `civictheme_content_approver` roles.

### Where do I change the links in the footer?

In the `Footer` menu, at `/admin/structure/menu/manage/civictheme-footer`. Its links render as a column in the middle of the footer, between the logo row and the Acknowledgement of Country. Add the policy pages and other site-wide links there rather than typing them into the `Footer - Copyright` block, which holds the copyright line and the CivicTheme credit.
