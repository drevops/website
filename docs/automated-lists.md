# Automated lists

The Automated list paragraph renders `views.view.civictheme_automated_list`, embedding one execution of that view per instance placed on a page.

## Every list has a pager of its own

Views identifies a pager by an **element id**, and the page a pager is on is the slot at that index in the `page` query parameter: `?page=0,1` puts the first element on page 1 and the second on page 2. The element id belongs to the view display, so every embedded instance inherits the same one and, left alone, every list on a page reads and writes the same slot.

`AutomatedListPagerHook` hands each instance its own element id through `hook_civictheme_automated_list_view_alter()`, which CivicTheme fires after it has finished with the pager and before the view runs. Ids are handed out in the order the lists render, starting at 0, so a page with one list keeps the plain `?page=N` URLs it has always had.

Points worth knowing before changing any of this:

- **`pager.options.id` in the exported view stays at `0`.** It is the default a single list uses; the hook overrides it per instance. Raising it there would only move every list to the same higher slot.
- **The id goes on the pager plugin, not only on the display option.** `DisplayPluginBase::getPlugin('pager')` builds its pager once and hands back that same instance forever, and `setOption()` does not invalidate it - so a `setOption()` on its own never reaches the pager the view actually runs with. The hook writes both, and the display's option is kept in step purely so the two cannot be read as disagreeing.
- **A list only takes an id if its pager paginates.** A list with a limit type of *Limited* uses the `some` pager, which has no pages to keep apart, and an id spent on it would leave a dead slot in every URL on the page.
- **Pages already in the URL are put back before a list runs.** A pager link names a page for every element the pager manager knows about when the link is built, and each list registers its element only when its own turn comes. Without restoring them first, the list that runs first would emit links naming only its own element and send every later list back to page 1. Only slots 0 to 63 are restored: the parameter is user input, and every slot restored from it widens each pager URL on the page, so a hand-written one must not be able to set that width.

Only lists rendered together need distinct ids, so the sequence restarts on each request.

## Related

- [Development agreements](development.md) - function visibility conventions the hook follows
- [Testing](testing.md) - PHPUnit and Behat conventions
