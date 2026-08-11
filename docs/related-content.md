# Related content and topic pages

Excluding the site navigation, most pages on this site had exactly one inbound internal link, and for the majority of them that link was a pager page. Nothing was unreachable, but a post published a while ago was reachable only by paging the blog listing. Two things address that, and they work together.

## An Automated list can follow the page's own topics

The Automated list paragraph carries a **Use topics of the current page** checkbox (`field_c_p_list_topics_from_page`). With it on, the list ignores the Topics chosen on the paragraph and matches the topics of the node it is placed on instead, which turns the same component into a related-content list. Nothing new was built to do this: it is the Automated list, configured differently.

`drevops_civictheme_automated_list_view_alter()` in `web/themes/custom/drevops/includes/automated_list.inc` swaps the Topics contextual argument just before the view runs. CivicTheme fires that alter through the theme manager as well as the module handler, which is why this lives in the theme alongside the rest of the component's theming rather than in a module.

Points worth knowing before changing it:

- **The page being viewed is already excluded.** The view's fourth contextual filter is `nid` with `not` set, and it takes its value from the route, so a related list never lists the post it sits on. `_civictheme_automated_list__update_view()` passes only three arguments, which is what leaves that one to its route default.
- **The topics argument is position 1.** The arguments are ordered content type, topics, site sections. Reordering them in the view would silently point the swap at the wrong filter.
- **A post with no topics gets `none`, not `all`.** Without that, an empty topic set would fall through to the view's `all` default and advertise the whole site as related.
- **An empty result hides the heading.** `drevops_preprocess_paragraph__civictheme_automated_list()` clears the title when the list found nothing, so a post whose topics nothing else shares does not render a heading introducing empty space.

`do_base_deploy_add_related_lists()` puts one of these lists on every existing blog post. It is skipped for a post that already has one, so it is safe to re-run.

## Topic pages

Topic terms are the second, stable inbound link: unlike a pager page, a topic page does not change what it points at as content is added.

- `pathauto.pattern.civictheme_topics` puts them at `/topics/<name>`.
- `do_base_deploy_alias_topic_terms()` hands every existing topic back to that pattern. Topics created programmatically carry `PathautoState::SKIP`, which is why most of them had no alias at all and a bulk generate would not give them one.
- The topic tags at the foot of a post link to these pages. `_drevops_node_add_topic_tags()` reads the referenced terms rather than their labels so each tag gets a `url`, which is what the `civictheme:tag` component turns into a link.
- `views.view.taxonomy_term` renders term pages as a CivicTheme promo-card grid with a full pager, rather than core's teaser list. This is the shared term view, so it applies to every vocabulary - acceptable because no other vocabulary's terms are linked from the site.
- Topic pages are listed in the XML sitemap.

## Related

- [SEO](seo.md) - meta tags, social share cards and structured data
- [Development agreements](development.md) - deploy hook conventions these follow
