<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\drupal_helpers\Helper;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Flip every component paragraph to the dark theme.
 *
 * The design renders the whole site dark. CivicTheme themes each component
 * individually through the shared `field_c_p_theme` field, and list components
 * theme their rendered items separately through `field_c_p_list_item_theme`, so
 * this switches both - across every bundle - to `dark`. The revision is updated
 * in place so the referencing entity revision keeps resolving to it. Content
 * created by later deploy hooks is built dark directly.
 */
function do_base_deploy_components_dark(?array &$sandbox): ?string {
  $fields = ['field_c_p_theme', 'field_c_p_list_item_theme'];

  return Helper::entity($sandbox)->batchEntity('paragraph', NULL, static function ($paragraph) use ($fields): void {
    $changed = FALSE;

    foreach ($fields as $field) {
      if (!$paragraph->hasField($field)) {
        continue;
      }

      if ($paragraph->get($field)->value === 'dark') {
        continue;
      }

      $paragraph->set($field, 'dark');
      $changed = TRUE;
    }

    if (!$changed) {
      return;
    }

    $paragraph->setNewRevision(FALSE);
    $paragraph->save();
  });
}

/**
 * Flip CivicTheme block content to the dark theme.
 *
 * Page-chrome blocks (the mobile navigation drawer, banner, social links)
 * carry their own `field_c_b_theme`, and the mobile navigation also themes
 * its trigger through `field_c_b_trigger_theme`. The mobile navigation ships
 * light, so it ignored the dark design; switch every block - and its
 * trigger - to dark so the whole site, including the mobile drawer, is dark.
 */
function do_base_deploy_blocks_dark(?array &$sandbox): ?string {
  $fields = ['field_c_b_theme', 'field_c_b_trigger_theme'];

  return Helper::entity($sandbox)->batchEntity('block_content', NULL, static function ($block) use ($fields): void {
    $changed = FALSE;

    foreach ($fields as $field) {
      if (!$block->hasField($field)) {
        continue;
      }

      if ($block->get($field)->value === 'dark') {
        continue;
      }

      $block->set($field, 'dark');
      $changed = TRUE;
    }

    if (!$changed) {
      return;
    }

    $block->setNewRevision(FALSE);
    $block->save();
  });
}

/**
 * Rebuild the homepage from CivicTheme components.
 *
 * The hero is the node banner (populated, not cleared). Every section is built
 * from CivicTheme paragraph components - manual lists of snippets for the
 * services, stats, trust, "why" and process sections, and a callout for the
 * closing CTA - with the bespoke design treatments driven by `field_do_style`.
 * No markup is stored as content. New references are saved before the previous
 * paragraphs are deleted, so a failed save never leaves dangling references.
 */
function do_base_deploy_homepage(): string {
  // Resolve the configured front page rather than assuming a fixed node ID.
  $front = (string) \Drupal::config('system.site')->get('page.front');
  $node = preg_match('#^/node/(\d+)$#', $front, $matches) ? Node::load((int) $matches[1]) : NULL;

  if (!$node instanceof Node || !$node->hasField('field_c_n_components')) {
    return 'Homepage node not found - skipped.';
  }

  $components = [
    _do_base_manual_list('', 1, 'numbered', [
      _do_base_snippet('Website Delivery', "We build your site with automated testing and CI/CD from the first commit, so what launches is solid from day one, not a prototype you'll be fixing after go-live. AI-assisted delivery gets you there faster, at the same tested standard."),
      _do_base_snippet('Ongoing Support', 'Proactive maintenance from the people who built your platform. Security updates, monitoring, continuous improvement, and a direct line with no layers in between.'),
      _do_base_snippet('Upgrades & Migrations', 'Running an end-of-life Drupal 7 or 9 site? We handle the full migration with test coverage and zero-downtime deployments, so you stay compliant and your users never notice the switch.'),
    ], 'What we do'),
    _do_base_manual_list('Faster, without lowering the bar.', 1, 'dotted', [
      _do_base_snippet('The same quality, in a fraction of the time', "AI does the heavy lifting on production work, so builds that used to take weeks take days. Same automated tests, same CI, same code you can read on GitHub. The quality bar doesn't move."),
      _do_base_snippet('"Isn\'t AI-written code risky?"', "Not the way we do it. Every change is reviewed, and every build is covered by automated tests and CI before it ships. AI speeds up the writing, not the checking. The guardrails that make this work are our own, and they're open source, so you can see exactly how it runs."),
      _do_base_snippet('Your code and data stay yours', 'We control the models and the data handling, and nothing trains on your project. It\'s all written down in our <a href="https://www.drevops.com/responsible-ai">Responsible AI policy</a>.'),
    ], 'AI-assisted delivery'),
    _do_base_manual_list('', 2, 'stat', [
      _do_base_fact_card('1', 'To set up CI/CD on a new project', 'day'),
      _do_base_fact_card('10', 'Delivering reliable platforms', 'yrs'),
      _do_base_fact_card('40', 'Open-source tools we maintain', '+'),
      _do_base_fact_card('0', 'Excuses when something breaks'),
    ], 'The essentials'),
    _do_base_manual_list("Trusted on projects where failure isn't an option.", 4, 'trust', [
      _do_base_snippet('Victorian Government', "Delivered Australia's first Docker-based government Drupal platform."),
      _do_base_snippet('Australian Defence', 'Multiple classified platforms with complex security and compliance requirements.'),
      _do_base_snippet('GovCMS', "Drupal platform delivery on Australia's government hosting infrastructure."),
      _do_base_snippet('Education', 'University platforms with ongoing support, leading to internal referrals across departments.'),
    ], 'Track record'),
    _do_base_manual_list('No filler. No overhead. Just good engineering.', 1, 'dotted', [
      _do_base_snippet('Automated testing is not optional', "Every platform ships with a full test suite. Functional, unit, and visual regression tests run on every commit. If it's not tested, it doesn't deploy."),
      _do_base_snippet('One team, zero handovers', 'We handle development, DevOps, and production support. One team with full context, no vendors blaming each other, no knowledge lost between handoffs.'),
      _do_base_snippet('Pricing that makes sense', "Flat-rate pricing with standard and rapid response options. We'll tell you what it costs upfront. No retainer games, no billable surprises, no markup on markup."),
      _do_base_snippet('Direct line to the engineers', 'You talk to the people building your platform. We manage the project without adding layers between you and the work. Fast communication, honest updates, no runaround.'),
    ]),
    _do_base_manual_list('A clear path from kickoff to ongoing support.', 1, 'numbered', [
      _do_base_snippet('Discovery', 'We review your website, understand your requirements and constraints, and scope the work, including whether AI-assisted delivery is the right fit. You get a clear proposal with flat-rate pricing before any work begins.'),
      _do_base_snippet('Delivery', 'Your site is built with automated testing and CI/CD from the first commit, with AI accelerating the production work and every change reviewed before it lands. Regular check-ins, transparent progress, and no surprises at the end.'),
      _do_base_snippet('Ongoing support', 'The same people who built your site maintain it. Security updates, continuous improvement, and proactive monitoring on a prepaid support arrangement.'),
    ], 'How we work'),
    _do_base_callout('Let\'s talk about your <span class="dr-underline">website.</span>', '<p>Tell us where things stand, what\'s working, and what\'s not. We\'ll be straight with you about whether we\'re the right fit.</p><p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="/contact">Talk to us</a></p>'),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    _do_base_stage_banner(
      $node,
      '<span class="ct-banner__eyebrow">Reliable websites, delivered faster</span>Your website<br><span class="dr-word-accent">can\'t afford to wait.</span>',
      'We build and support reliable websites for businesses and organisations that depend on them.<br>Now delivered faster and cheaper with AI-assisted development.',
      ['title' => 'Talk to us', 'uri' => '/contact']
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  _do_base_delete_entities($stale);

  return 'Homepage rebuilt.';
}

/**
 * Rebuild the Services page from CivicTheme components.
 *
 * The hero is the node banner. Each service is a CivicTheme promo (title +
 * rich-text body + button); the "our approach" grid is a dotted manual list of
 * snippets; the CTA is a callout. No markup is stored as content.
 */
function do_base_deploy_services(): string {
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'uuid' => 'b78dd34e-e1b2-480a-9056-80902d410008',
  ]);
  $node = reset($nodes);

  if (!$node instanceof Node || !$node->hasField('field_c_n_components')) {
    return 'Services node not found - skipped.';
  }

  $components = [
    _do_base_promo('Website Delivery', _do_base_service_content(
      'From requirements to production in one engagement.',
      [
        'Full Drupal website builds delivered with automated testing, CI/CD pipelines, and production-ready infrastructure. We handle the architecture, development, theming, and deployment - your team gets a solid platform, not a prototype that needs fixing after launch.',
        'Every project ships with a complete test suite, documentation, and a handover that actually works.',
      ],
      [
        'Technical architecture and planning',
        'Custom module and theme development',
        'Automated testing (PHPUnit, Behat, Cypress)',
        'CI/CD pipeline setup (GitHub Actions, CircleCI)',
        'Content migration and data import',
        'Hosting setup and go-live support',
      ],
      'Typical engagement', '$40K - $180K'
    ), 'Discuss your project', '/contact'),
    _do_base_promo('Ongoing Support', _do_base_service_content(
      'The same senior team that built it, maintaining it.',
      [
        'Proactive platform maintenance from the engineers who built your site. Security updates, Drupal core and module patches, performance monitoring, and continuous improvement - all on a predictable prepaid arrangement.',
        'No ticket queues, no outsourced support desks. You talk directly to the people who know your codebase.',
      ],
      [
        'Security patches and Drupal updates',
        'Uptime and performance monitoring',
        'Bug fixes and minor enhancements',
        'Monthly reporting and recommendations',
        'Direct Slack/email access to engineers',
        'Priority response for critical issues',
      ],
      'From', '$740 / month'
    ), 'Get a support quote', '/contact'),
    _do_base_promo('Upgrades & Migrations', _do_base_service_content(
      'Move off end-of-life Drupal without breaking anything.',
      [
        'Drupal 7 and 9 are end-of-life. Drupal 10 follows in December 2026. We handle the full migration with test coverage and zero-downtime deployments, so your organisation stays compliant and your users stay unaffected.',
        'We assess your current platform, map out module compatibility, migrate custom code, and deliver an upgraded site with full test coverage.',
      ],
      [
        'Platform audit and risk assessment',
        'Module compatibility analysis',
        'Custom code migration and refactoring',
        'Data migration and content integrity checks',
        'Automated test suite for the upgraded site',
        'Zero-downtime deployment and rollback plan',
      ],
      'Typical engagement', '$25K - $120K'
    ), 'Book a free assessment', '/contact'),
    _do_base_manual_list('', 2, 'dotted', [
      _do_base_snippet('Senior engineers only', 'No juniors on your project. Every person who touches your code has 10+ years of Drupal experience.'),
      _do_base_snippet('Flat-rate pricing', 'We quote a fixed price upfront. No hourly billing surprises, no retainer games, no scope creep charges.'),
      _do_base_snippet('Tested by default', "Every platform ships with automated tests. If it's not tested, it doesn't deploy. No exceptions."),
      _do_base_snippet('Direct communication', 'You talk to the engineers building your site. No project managers relaying messages, no layers in between.'),
    ], 'Our approach'),
    _do_base_callout('Ready to talk about your <span class="dr-underline">platform?</span>', '<p>Tell us where things stand. We\'ll be straight with you about whether we\'re the right fit.</p><p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="/contact">Get in touch</a></p>'),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    _do_base_stage_banner(
      $node,
      'Engineering that keeps<br><span class="dr-word-accent">your platform running.</span>',
      "We deliver, support, and upgrade Drupal websites for organisations where downtime, security gaps, and slow development aren't acceptable."
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  _do_base_delete_entities($stale);

  return 'Services page rebuilt.';
}

/**
 * Rebuild the Contact page to the design.
 *
 * Keeps the existing contact webform (rendered through a CivicTheme webform
 * paragraph) between the hero and the contact-detail sections.
 */
function do_base_deploy_contact(): string {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias('/contact');

  if (!preg_match('#^/node/(\d+)$#', $path, $matches)) {
    return 'Contact node not found - skipped.';
  }

  $node = Node::load((int) $matches[1]);

  if (!$node instanceof Node || !$node->hasField('field_c_n_components')) {
    return 'Contact node not usable - skipped.';
  }

  // The webform leads (the primary action), followed by the direct contact
  // details and the "what to expect" steps rendered as rich-text content.
  $components = [
    Paragraph::create([
      'type' => 'civictheme_webform',
      'field_c_p_theme' => 'dark',
      'field_c_p_webform' => 'contact',
    ]),
    _do_base_content_richtext(_do_base_contact_info()),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    _do_base_stage_banner(
      $node,
      "Let's talk about your platform.",
      "Whether you need a new Drupal build, help upgrading from an end-of-life version, or ongoing support from a senior team - we're happy to have an honest conversation about where things stand."
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  _do_base_delete_entities($stale);

  return 'Contact page rebuilt.';
}

/**
 * Seed the design demo blog article.
 *
 * Idempotent: matches the existing article by title, otherwise creates it.
 */
function do_base_deploy_blog_demo(): string {
  $title = 'Why Your Drupal CI Pipeline Is Slower Than It Should Be';
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  $existing = $storage->loadByProperties([
    'type' => 'civictheme_page',
    'title' => $title,
  ]);

  $node = $existing ? reset($existing) : Node::create([
    'type' => 'civictheme_page',
    'title' => $title,
  ]);

  $node->set('status', 1);

  // The article body is genuine editorial rich-text (headings, tables, code,
  // blockquotes, a CTA), which is exactly what civictheme_content with the
  // rich-text format is for - no full_html, no stored design markup.
  $body = _do_base_content_richtext(_do_base_blog_body());
  $body->save();

  $previous = $node->hasField('field_c_n_components') ? $node->get('field_c_n_components')->referencedEntities() : [];
  $stale = array_merge(_do_base_stage_banner($node, $title), $previous);

  if ($node->hasField('field_c_n_components')) {
    $node->set('field_c_n_components', [$body]);
  }

  $node->save();
  _do_base_delete_entities($stale);

  return 'Blog demo article seeded.';
}

/**
 * Populate the node banner so it renders as the page hero.
 *
 * The design's hero is the CivicTheme intro banner (themed in the subtheme),
 * not a content section. This sets the banner title, type and theme. When a
 * subtitle is given it is added as a CivicTheme content component inside the
 * banner (CivicTheme renders `field_c_n_banner_components` into the banner's
 * content slot), optionally with a call-to-action button. Previous banner
 * components are returned for deletion after the save.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node whose banner becomes the hero.
 * @param string $title
 *   The hero heading.
 * @param string $subtitle
 *   The hero subtitle; empty for none.
 * @param array|null $cta
 *   Optional call-to-action button as ['title' => string, 'uri' => string].
 *
 * @return \Drupal\Core\Entity\EntityInterface[]
 *   The previously referenced banner paragraphs, pending deletion.
 */
function _do_base_stage_banner(Node $node, string $title, string $subtitle = '', ?array $cta = NULL): array {
  if ($node->hasField('field_c_n_banner_title')) {
    $node->set('field_c_n_banner_title', $title);
  }

  if ($node->hasField('field_c_n_banner_type')) {
    $node->set('field_c_n_banner_type', 'intro');
  }

  if ($node->hasField('field_c_n_banner_theme')) {
    $node->set('field_c_n_banner_theme', 'dark');
  }

  // The design hero is the dark intro banner with its atmospheric glow, not an
  // image. Clear any background or featured image left on the node from the
  // demo content so the hero renders dark with legible white text.
  foreach (['field_c_n_banner_background', 'field_c_n_banner_featured_image'] as $image_field) {
    if ($node->hasField($image_field)) {
      $node->set($image_field, NULL);
    }
  }

  $previous = [];

  foreach (['field_c_n_banner_components', 'field_c_n_banner_components_bott'] as $field) {
    if (!$node->hasField($field)) {
      continue;
    }

    $previous = array_merge($previous, $node->get($field)->referencedEntities());
    $node->set($field, []);
  }

  if ($subtitle !== '' && $node->hasField('field_c_n_banner_components')) {
    $intro = _do_base_banner_intro($subtitle, $cta);
    $intro->save();
    $node->set('field_c_n_banner_components', [$intro]);
  }

  return $previous;
}

/**
 * Build the dark CivicTheme content component that holds the hero subtitle.
 *
 * @param string $subtitle
 *   The hero subtitle.
 * @param array|null $cta
 *   Optional call-to-action button as ['title' => string, 'uri' => string],
 *   rendered as a CivicTheme button after the subtitle.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved content paragraph for the banner content slot.
 */
function _do_base_banner_intro(string $subtitle, ?array $cta): Paragraph {
  $html = '<p class="ct-text-large">' . $subtitle . '</p>';

  if ($cta !== NULL) {
    $html .= '<p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="' . $cta['uri'] . '">' . $cta['title'] . '</a></p>';
  }

  return Paragraph::create([
    'type' => 'civictheme_content',
    'field_c_p_theme' => 'dark',
    'field_c_p_content' => [
      'value' => $html,
      'format' => 'civictheme_rich_text',
    ],
  ]);
}

/**
 * Build a dark CivicTheme snippet (title + summary) list item.
 *
 * @param string $title
 *   The snippet title.
 * @param string $summary
 *   The snippet summary.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved snippet paragraph.
 */
function _do_base_snippet(string $title, string $summary): Paragraph {
  return Paragraph::create([
    'type' => 'civictheme_snippet',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_summary' => $summary,
  ]);
}

/**
 * Build a dark Fact card (a large figure above a label).
 *
 * @param string $fact
 *   The figure, e.g. "0" or "40".
 * @param string $label
 *   The label beneath the figure.
 * @param string $suffix
 *   A small unit after the figure, e.g. "day", "yrs" or "+".
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved fact card paragraph.
 */
function _do_base_fact_card(string $fact, string $label, string $suffix = ''): Paragraph {
  return Paragraph::create([
    'type' => 'do_fact_card',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $fact,
    'field_do_suffix' => $suffix,
    'field_c_p_summary' => $label,
  ]);
}

/**
 * Build a dark CivicTheme manual list of snippet items.
 *
 * The `$style` is stored in `field_do_style`; the theme renders the bespoke
 * design treatment (numbered, stat, trust, dotted) from a single list modifier
 * class, styling the snippet children with CSS - no markup is stored.
 *
 * @param string $title
 *   The section heading.
 * @param int $columns
 *   Column count (1-4).
 * @param string $style
 *   The design style key (numbered, stat, trust, dotted).
 * @param \Drupal\paragraphs\Entity\Paragraph[] $items
 *   The snippet list items.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved manual list paragraph referencing the saved items.
 */
function _do_base_manual_list(string $title, int $columns, string $style, array $items, string $eyebrow = ''): Paragraph {
  foreach ($items as $item) {
    $item->save();
  }

  return Paragraph::create([
    'type' => 'civictheme_manual_list',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_list_column_count' => $columns,
    'field_do_style' => $style,
    'field_do_eyebrow' => $eyebrow,
    'field_c_p_list_items' => $items,
  ]);
}

/**
 * Build a dark CivicTheme callout (title + rich-text body + link).
 *
 * @param string $title
 *   The callout heading.
 * @param string $body
 *   The callout body, rendered through the rich-text format.
 * @param string $link_title
 *   The link text.
 * @param string $link_uri
 *   The link URI.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved callout paragraph.
 */
function _do_base_callout(string $title, string $body, string $link_title = '', string $link_uri = ''): Paragraph {
  $values = [
    'type' => 'civictheme_callout',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_content' => [
      'value' => $body,
      'format' => 'civictheme_rich_text',
    ],
  ];

  // The callout button renders external links (a mailto) with a new-window
  // target and an arrow. Only add the button for a real call-to-action link;
  // the homepage CTA renders its email inside the body as a plain link instead.
  if ($link_uri !== '') {
    $values['field_c_p_links'] = [['uri' => _do_base_link_uri($link_uri), 'title' => $link_title]];
  }

  return Paragraph::create($values);
}

/**
 * Build a dark CivicTheme promo (title + rich-text body + button).
 *
 * Used for the service-detail rows: the title is the service name, the body
 * carries the tagline, description, "what's included" list and price as
 * rich-text, and the link is the call-to-action button.
 *
 * @param string $title
 *   The promo heading.
 * @param string $content
 *   The promo body, rendered through the rich-text format.
 * @param string $link_title
 *   The button text.
 * @param string $link_uri
 *   The button URI.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved promo paragraph.
 */
function _do_base_promo(string $title, string $content, string $link_title, string $link_uri): Paragraph {
  return Paragraph::create([
    'type' => 'civictheme_promo',
    'field_c_p_theme' => 'dark',
    'field_c_p_title' => $title,
    'field_c_p_content' => [
      'value' => $content,
      'format' => 'civictheme_rich_text',
    ],
    'field_c_p_link' => [
      'uri' => _do_base_link_uri($link_uri),
      'title' => $link_title,
    ],
  ]);
}

/**
 * Assemble the rich-text body of a service-detail promo.
 *
 * Produces only tags permitted by the `civictheme_rich_text` format: a large
 * tagline lead, description paragraphs, a "what's included" heading and list,
 * and a small price line. No `full_html`, no bespoke markup.
 *
 * @param string $tagline
 *   The one-line service tagline.
 * @param string[] $paras
 *   Description paragraphs.
 * @param string[] $includes
 *   The "what's included" list items.
 * @param string $price_label
 *   The price label (e.g. "Typical engagement").
 * @param string $price_value
 *   The price value (e.g. "$40K - $180K").
 *
 * @return string
 *   The rich-text HTML body.
 */
function _do_base_service_content(string $tagline, array $paras, array $includes, string $price_label, string $price_value): string {
  $html = '<p class="ct-text-large">' . $tagline . '</p>';

  foreach ($paras as $para) {
    $html .= '<p>' . $para . '</p>';
  }

  $html .= "<h3>What's included</h3><ul>";

  foreach ($includes as $include) {
    $html .= '<li>' . $include . '</li>';
  }

  $html .= '</ul>';

  return $html . '<p class="ct-text-small"><strong>' . $price_label . '</strong> ' . $price_value . '</p>';
}

/**
 * Normalise a link URI for a Drupal link field.
 *
 * Root-relative paths are stored with the `internal:` scheme; absolute URLs
 * and schemes such as `mailto:` are left untouched.
 *
 * @param string $uri
 *   The raw URI.
 *
 * @return string
 *   The normalised URI.
 */
function _do_base_link_uri(string $uri): string {
  return str_starts_with($uri, '/') ? 'internal:' . $uri : $uri;
}

/**
 * Delete entities staged for removal after the referencing node was saved.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   Entities to delete.
 */
function _do_base_delete_entities(array $entities): void {
  foreach ($entities as $entity) {
    $entity->delete();
  }
}

/**
 * Build a dark CivicTheme content paragraph from rich-text markup.
 *
 * @param string $html
 *   The body markup, rendered through the rich-text format.
 *
 * @return \Drupal\paragraphs\Entity\Paragraph
 *   An unsaved content paragraph.
 */
function _do_base_content_richtext(string $html): Paragraph {
  return Paragraph::create([
    'type' => 'civictheme_content',
    'field_c_p_theme' => 'dark',
    'field_c_p_content' => [
      'value' => $html,
      'format' => 'civictheme_rich_text',
    ],
  ]);
}

/**
 * Build the rich-text body for the contact details and "what to expect" steps.
 *
 * @return string
 *   The rich-text HTML body.
 */
function _do_base_contact_info(): string {
  return <<<'HTML'
<h3>Email us directly</h3>
<p><a href="mailto:info@drevops.com">info@drevops.com</a></p>
<p class="ct-text-small">We typically respond within one business day.</p>
<h3>Call us</h3>
<p><a href="tel:+61430093538">04 3009 3538</a></p>
<p class="ct-text-small">Available weekdays, Melbourne time (AEST).</p>
<h3>Based in</h3>
<p>Melbourne, Australia</p>
<p class="ct-text-small">We work with organisations across Australia and New Zealand.</p>
<h3>What to expect</h3>
<ol>
<li>We'll review your message and respond within 24 hours.</li>
<li>A 30-minute call to understand your platform and goals.</li>
<li>A clear proposal with flat-rate pricing - no surprises.</li>
</ol>
HTML;
}

/**
 * Build the rich-text body for the demo blog article.
 *
 * Uses only tags permitted by the `civictheme_rich_text` format: headings,
 * paragraphs, striped tables, fenced code (highlighted client-side by the
 * highlight_js filter), blockquotes, a list and a CTA button.
 *
 * @return string
 *   The rich-text HTML body.
 */
function _do_base_blog_body(): string {
  return <<<'HTML'
<p class="ct-text-large">Most Drupal teams accept slow CI pipelines as a fact of life. Builds that take 15 minutes, test suites that timeout, and deployments that require a coffee break. It doesn't have to be this way.</p>
<p>We've audited dozens of Drupal CI pipelines across government, education, and enterprise organisations. The same problems come up repeatedly. Here's what we find and how to fix it.</p>
<h2>The usual suspects</h2>
<p>Before diving into solutions, it helps to understand where time actually goes in a typical Drupal CI build. We measured 24 pipelines across different hosting providers and CI platforms:</p>
<table class="ct-table ct-table--striped">
<thead>
<tr><th>Build Phase</th><th>Median Time</th><th>Worst Case</th><th>Optimised</th></tr>
</thead>
<tbody>
<tr><td>Docker image pull</td><td>2m 30s</td><td>6m 15s</td><td>15s</td></tr>
<tr><td>Composer install</td><td>1m 45s</td><td>4m 20s</td><td>12s</td></tr>
<tr><td>Database import</td><td>3m 10s</td><td>8m 00s</td><td>45s</td></tr>
<tr><td>PHPUnit tests</td><td>4m 20s</td><td>12m 00s</td><td>1m 30s</td></tr>
<tr><td>Behat/Cypress</td><td>6m 45s</td><td>18m 00s</td><td>2m 15s</td></tr>
<tr><td><strong>Total</strong></td><td><strong>18m 30s</strong></td><td><strong>48m 35s</strong></td><td><strong>4m 57s</strong></td></tr>
</tbody>
</table>
<p>The gap between worst-case and optimised is significant. Let's walk through the fixes.</p>
<h2>1. Stop pulling full Docker images on every build</h2>
<p>The single biggest time sink is rebuilding or pulling Docker images. Most CI configurations start with a base image pull on every run. If you're using <code>docker-compose pull</code> without layer caching, you're downloading gigabytes of data every build.</p>
<h3>The fix: pre-built CI images</h3>
<p>Build a dedicated CI image with your PHP extensions, Node version, and system dependencies baked in. Push it to your container registry and reference it directly:</p>
<pre><code class="language-yaml"># .circleci/config.yml
jobs:
  test:
    docker:
      - image: ghcr.io/your-org/drupal-ci:php8.3
        auth:
          username: $GITHUB_USER
          password: $GITHUB_TOKEN
    steps:
      - checkout
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/phpunit</code></pre>
<p>This alone typically cuts 2-4 minutes off every build.</p>
<h2>2. Cache Composer dependencies properly</h2>
<p>Composer install is deceptively slow because it resolves dependencies, downloads packages, and runs post-install scripts. Most of this work is redundant between builds.</p>
<pre><code class="language-bash">#!/usr/bin/env bash
# Restore Composer cache from CI provider's cache layer.
COMPOSER_HASH=$(md5sum composer.lock | cut -d' ' -f1)
CACHE_KEY="composer-v1-${COMPOSER_HASH}"

if [ -d "/tmp/composer-cache/${CACHE_KEY}" ]; then
  cp -r "/tmp/composer-cache/${CACHE_KEY}/vendor" ./vendor
  echo "Cache hit: restored vendor from ${CACHE_KEY}"
else
  composer install --no-interaction --prefer-dist
  mkdir -p "/tmp/composer-cache/${CACHE_KEY}"
  cp -r vendor "/tmp/composer-cache/${CACHE_KEY}/vendor"
  echo "Cache miss: saved vendor to ${CACHE_KEY}"
fi</code></pre>
<blockquote>
<p><strong>Key insight:</strong> Cache on <code>composer.lock</code> hash, not <code>composer.json</code>. The lock file captures exact versions, so the cache is only invalidated when dependencies actually change.</p>
</blockquote>
<h2>3. Sanitise and slim your test database</h2>
<p>Many teams import a full production database dump for testing. This is slow, wasteful, and a compliance risk. A typical government Drupal site has 500MB+ of database content that tests don't need.</p>
<p>What you actually need for CI:</p>
<ul>
<li>Schema and configuration (usually under 5MB)</li>
<li>A small set of representative content nodes</li>
<li>User accounts for test roles (admin, editor, anonymous)</li>
<li>Taxonomy terms and menu structures</li>
</ul>
<p>We use a sanitisation script that strips the database down to essentials:</p>
<pre><code class="language-php">/**
 * Sanitise database for CI usage.
 *
 * Removes user data, reduces content to representative sample,
 * and strips session/cache tables.
 */
function ci_sanitise_database(): void {
  $connection = \Drupal::database();

  // Truncate cache and session tables.
  $tables = $connection->schema()->findTables('cache_%');
  $tables = array_merge($tables, $connection->schema()->findTables('sessions'));
  foreach ($tables as $table) {
    $connection->truncate($table)->execute();
  }

  // Keep only 10 nodes per content type.
  $types = \Drupal::entityTypeManager()
    ->getStorage('node_type')
    ->loadMultiple();

  foreach (array_keys($types) as $type) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->sort('changed', 'DESC')
      ->range(10, 999999)
      ->accessCheck(FALSE)
      ->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage->loadMultiple($nids);
    $storage->delete($nodes);
  }
}</code></pre>
<h2>4. Parallelise your test suite</h2>
<p>Running all tests sequentially is the default in most setups. But PHPUnit supports parallel execution via tools like <code>paratest</code>, and Behat scenarios can be split across multiple containers.</p>
<table class="ct-table ct-table--striped">
<thead>
<tr><th>Strategy</th><th>Effort</th><th>Time Saved</th></tr>
</thead>
<tbody>
<tr><td>Paratest for PHPUnit</td><td>Low - drop-in replacement</td><td>40-60%</td></tr>
<tr><td>Split Behat by tag</td><td>Medium - needs CI config</td><td>50-70%</td></tr>
<tr><td>Parallel CI jobs</td><td>Medium - matrix builds</td><td>60-80%</td></tr>
<tr><td>Test selection (changed files only)</td><td>High - needs test mapping</td><td>70-90%</td></tr>
</tbody>
</table>
<h2>5. Integrate S3 for asset storage</h2>
<p>If your CI pipeline runs <code>drush sql:dump</code> and stores the result as a build artifact, you're wasting time on compression and upload. Use S3 (or equivalent) with a dedicated bucket:</p>
<pre><code class="language-bash"># Upload sanitised DB dump to S3 (run nightly, not per-build).
drush sql:dump --gzip --result-file=/tmp/db.sql.gz
aws s3 cp /tmp/db.sql.gz s3://ci-artifacts/drupal-db/latest.sql.gz

# In CI: download pre-built dump (fast, cached at edge).
aws s3 cp s3://ci-artifacts/drupal-db/latest.sql.gz /tmp/db.sql.gz
gunzip -c /tmp/db.sql.gz | drush sql:cli</code></pre>
<h2>The compound effect</h2>
<p>Each optimisation on its own saves a few minutes. Together, they transform the development experience. A team pushing 20 commits per day at 18 minutes per build is spending <strong>6 hours of CI time daily</strong>. Cut that to 5 minutes and you reclaim 4.3 hours - every day.</p>
<blockquote>
<p>Fast CI isn't a luxury. It's the difference between developers who test before merging and developers who push to main and hope for the best.</p>
</blockquote>
<p>If your Drupal CI pipeline takes more than 5 minutes, there's room to improve. We offer a free 30-minute review of your pipeline configuration - no commitment, just practical advice.</p>
<p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="mailto:info@drevops.com">Get a free CI review</a></p>
HTML;
}
