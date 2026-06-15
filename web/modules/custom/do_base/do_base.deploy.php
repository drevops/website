<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\do_base\ContentBuilder;
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
 * services, stats, trust, "why" and process sections, and a callout for
 * the closing CTA - with bespoke design treatments via `field_p_appearance`.
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
    ContentBuilder::manualList('', 1, 'numbered', [
      ContentBuilder::snippet('Website Delivery', "We build your site with automated testing and CI/CD from the first commit, so what launches is solid from day one, not a prototype you'll be fixing after go-live. AI-assisted delivery gets you there faster, at the same tested standard."),
      ContentBuilder::snippet('Ongoing Support', 'Proactive maintenance from the people who built your platform. Security updates, monitoring, continuous improvement, and a direct line with no layers in between.'),
      ContentBuilder::snippet('Upgrades & Migrations', 'Running an end-of-life Drupal 7 or 9 site? We handle the full migration with test coverage and zero-downtime deployments, so you stay compliant and your users never notice the switch.'),
    ], 'What we do'),
    ContentBuilder::manualList('Faster, without lowering the bar.', 1, 'dotted', [
      ContentBuilder::snippet('The same quality, in a fraction of the time', "AI does the heavy lifting on production work, so builds that used to take weeks take days. Same automated tests, same CI, same code you can read on GitHub. The quality bar doesn't move."),
      ContentBuilder::snippet('"Isn\'t AI-written code risky?"', "Not the way we do it. Every change is reviewed, and every build is covered by automated tests and CI before it ships. AI speeds up the writing, not the checking. The guardrails that make this work are our own, and they're open source, so you can see exactly how it runs."),
      ContentBuilder::snippet('Your code and data stay yours', 'We control the models and the data handling, and nothing trains on your project. It\'s all written down in our <a href="https://www.drevops.com/responsible-ai">Responsible AI policy</a>.'),
    ], 'AI-assisted delivery'),
    ContentBuilder::manualList('', 2, 'stat', [
      ContentBuilder::factCard('1', 'To set up CI/CD on a new project', 'day'),
      ContentBuilder::factCard('10', 'Delivering reliable platforms', 'yrs'),
      ContentBuilder::factCard('40', 'Open-source tools we maintain', '+'),
      ContentBuilder::factCard('0', 'Excuses when something breaks'),
    ], 'The essentials'),
    ContentBuilder::manualList("Trusted on projects where failure isn't an option.", 4, 'trust', [
      ContentBuilder::snippet('Victorian Government', "Delivered Australia's first Docker-based government Drupal platform."),
      ContentBuilder::snippet('Australian Defence', 'Multiple classified platforms with complex security and compliance requirements.'),
      ContentBuilder::snippet('GovCMS', "Drupal platform delivery on Australia's government hosting infrastructure."),
      ContentBuilder::snippet('Education', 'University platforms with ongoing support, leading to internal referrals across departments.'),
    ], 'Track record'),
    ContentBuilder::manualList('No filler. No overhead. Just good engineering.', 1, 'dotted', [
      ContentBuilder::snippet('Automated testing is not optional', "Every platform ships with a full test suite. Functional, unit, and visual regression tests run on every commit. If it's not tested, it doesn't deploy."),
      ContentBuilder::snippet('One team, zero handovers', 'We handle development, DevOps, and production support. One team with full context, no vendors blaming each other, no knowledge lost between handoffs.'),
      ContentBuilder::snippet('Pricing that makes sense', "Flat-rate pricing with standard and rapid response options. We'll tell you what it costs upfront. No retainer games, no billable surprises, no markup on markup."),
      ContentBuilder::snippet('Direct line to the engineers', 'You talk to the people building your platform. We manage the project without adding layers between you and the work. Fast communication, honest updates, no runaround.'),
    ]),
    ContentBuilder::manualList('A clear path from kickoff to ongoing support.', 1, 'numbered', [
      ContentBuilder::snippet('Discovery', 'We review your website, understand your requirements and constraints, and scope the work, including whether AI-assisted delivery is the right fit. You get a clear proposal with flat-rate pricing before any work begins.'),
      ContentBuilder::snippet('Delivery', 'Your site is built with automated testing and CI/CD from the first commit, with AI accelerating the production work and every change reviewed before it lands. Regular check-ins, transparent progress, and no surprises at the end.'),
      ContentBuilder::snippet('Ongoing support', 'The same people who built your site maintain it. Security updates, continuous improvement, and proactive monitoring on a prepaid support arrangement.'),
    ], 'How we work'),
    ContentBuilder::callout('Let\'s talk about your <span class="do-underline">website.</span>', '<p>Tell us where things stand, what\'s working, and what\'s not. We\'ll be straight with you about whether we\'re the right fit.</p><p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="/contact">Talk to us</a></p>'),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    ContentBuilder::stageBanner(
      $node,
      '<span class="ct-banner__eyebrow">Reliable websites, delivered faster</span>Your website<br><span class="do-word-accent">can\'t afford to wait.</span>',
      'We build and support reliable websites for businesses and organisations that depend on them.<br>Now delivered faster and cheaper with AI-assisted development.',
      ['title' => 'Talk to us', 'uri' => '/contact']
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  ContentBuilder::deleteEntities($stale);

  return 'Homepage rebuilt.';
}

/**
 * Rebuild the Services page from CivicTheme components.
 *
 * The hero is the node banner. Each service is a Service detail paragraph; the
 * "our approach" grid is a dotted manual list of snippets; the CTA is a
 * callout.
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
    ContentBuilder::serviceDetail('Website Delivery',
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
      'Typical engagement', '$40K - $180K',
      'Discuss your project', '/contact'
    ),
    ContentBuilder::serviceDetail('Ongoing Support',
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
      'From', '$740 / month',
      'Get a support quote', '/contact'
    ),
    ContentBuilder::serviceDetail('Upgrades & Migrations',
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
      'Typical engagement', '$25K - $120K',
      'Book a free assessment', '/contact'
    ),
    ContentBuilder::manualList('', 2, 'dotted', [
      ContentBuilder::snippet('Senior engineers only', 'No juniors on your project. Every person who touches your code has 10+ years of Drupal experience.'),
      ContentBuilder::snippet('Flat-rate pricing', 'We quote a fixed price upfront. No hourly billing surprises, no retainer games, no scope creep charges.'),
      ContentBuilder::snippet('Tested by default', "Every platform ships with automated tests. If it's not tested, it doesn't deploy. No exceptions."),
      ContentBuilder::snippet('Direct communication', 'You talk to the engineers building your site. No project managers relaying messages, no layers in between.'),
    ], 'Our approach'),
    ContentBuilder::callout('Ready to talk about your <span class="do-underline">platform?</span>', '<p>Tell us where things stand. We\'ll be straight with you about whether we\'re the right fit.</p><p><a class="ct-button ct-theme-dark ct-button--secondary ct-button--large" href="/contact">Get in touch</a></p>'),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    ContentBuilder::stageBanner(
      $node,
      'Engineering that keeps<br><span class="do-word-accent">your platform running.</span>',
      "We deliver, support, and upgrade Drupal websites for organisations where downtime, security gaps, and slow development aren't acceptable."
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  ContentBuilder::deleteEntities($stale);

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
    ContentBuilder::contentRichText(ContentBuilder::contactInfo()),
  ];

  foreach ($components as $component) {
    $component->save();
  }

  $stale = array_merge(
    ContentBuilder::stageBanner(
      $node,
      "Let's talk about your platform.",
      "Whether you need a new Drupal build, help upgrading from an end-of-life version, or ongoing support from a senior team - we're happy to have an honest conversation about where things stand."
    ),
    $node->get('field_c_n_components')->referencedEntities()
  );
  $node->set('field_c_n_components', $components);
  $node->save();
  ContentBuilder::deleteEntities($stale);

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
  $body = ContentBuilder::contentRichText(ContentBuilder::blogBody());
  $body->save();

  $previous = $node->hasField('field_c_n_components') ? $node->get('field_c_n_components')->referencedEntities() : [];
  $stale = array_merge(ContentBuilder::stageBanner($node, $title), $previous);

  if ($node->hasField('field_c_n_components')) {
    $node->set('field_c_n_components', [$body]);
  }

  $node->save();
  ContentBuilder::deleteEntities($stale);

  return 'Blog demo article seeded.';
}

/**
 * Set the blog listing page hero to the design's headline.
 *
 * The /blog node falls back to the bare "Blog" title; the design uses a
 * descriptive headline above the article grid.
 */
function do_base_deploy_blog_listing(): string {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias('/blog');

  if (!preg_match('#^/node/(\d+)$#', $path, $matches)) {
    return 'Blog listing node not found - skipped.';
  }

  $node = Node::load((int) $matches[1]);

  if (!$node instanceof Node || !$node->hasField('field_c_n_banner_title')) {
    return 'Blog listing node not usable - skipped.';
  }

  $node->set('field_c_n_banner_title', '<span class="ct-banner__eyebrow">Blog</span>Practical engineering insights<br>from the teams we work with.');

  if ($node->hasField('field_c_n_banner_type')) {
    $node->set('field_c_n_banner_type', 'page');
  }

  if ($node->hasField('field_c_n_banner_theme')) {
    $node->set('field_c_n_banner_theme', 'dark');
  }

  foreach (['field_c_n_banner_background', 'field_c_n_banner_featured_image'] as $image_field) {
    if ($node->hasField($image_field)) {
      $node->set($image_field, NULL);
    }
  }

  // Featured article: clone the article grid into a single most-recent card,
  // shown full-width above the grid (the design's featured post). Idempotent -
  // skip when a single-item list is already first.
  if ($node->hasField('field_c_n_components')) {
    $components = $node->get('field_c_n_components')->referencedEntities();
    $first = $components[0] ?? NULL;
    $already = $first instanceof Paragraph && $first->bundle() === 'civictheme_automated_list' && (int) $first->get('field_c_p_list_limit')->value === 1;

    if (!$already) {
      $grid = NULL;

      foreach ($components as $component) {
        if ($component->bundle() === 'civictheme_automated_list') {
          $grid = $component;
          break;
        }
      }

      if ($grid instanceof Paragraph) {
        $featured = $grid->createDuplicate();
        $featured->set('field_c_p_list_limit', 1);
        $featured->set('field_c_p_list_limit_type', 'limited');
        $featured->set('field_c_p_list_column_count', 1);
        $featured->save();

        array_unshift($components, $featured);
        $node->set('field_c_n_components', $components);
      }
    }
  }

  $node->save();

  return 'Blog listing banner updated.';
}

/**
 * Apply the design's blog-post treatment.
 *
 * Blog posts (pages that carry topics) ship with tags hidden and a default
 * banner. The design reveals the topic pills and renders the header as a fade
 * hero over the featured image, so switch the tags on and, where the page has a
 * featured image, set the banner to the "fade" type.
 */
function do_base_deploy_blog_tags(?array &$sandbox): ?string {
  return Helper::entity($sandbox)->batchEntity('node', NULL, static function ($node): void {
    if ($node->bundle() !== 'civictheme_page' || !$node->hasField('field_c_n_topics') || $node->get('field_c_n_topics')->isEmpty()) {
      return;
    }

    $changed = FALSE;

    if ($node->hasField('field_c_n_hide_tags') && $node->get('field_c_n_hide_tags')->value !== '0') {
      $node->set('field_c_n_hide_tags', 0);
      $changed = TRUE;
    }

    $has_image = $node->hasField('field_c_n_banner_featured_image') && !$node->get('field_c_n_banner_featured_image')->isEmpty();

    if ($has_image && $node->hasField('field_c_n_banner_type') && $node->get('field_c_n_banner_type')->value !== 'fade') {
      $node->set('field_c_n_banner_type', 'fade');
      $changed = TRUE;
    }

    if (!$changed) {
      return;
    }

    $node->setNewRevision(FALSE);
    $node->save();
  });
}
