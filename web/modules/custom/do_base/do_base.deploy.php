<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeColorManager;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\drupal_helpers\Helper;
use Drupal\drupal_helpers\Report\Reporter;
use Drupal\entity_usage\RecreateTrackingDataForFieldQueuer;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\path_alias\PathAliasInterface;
use Drupal\pathauto\PathautoState;
use Drupal\search_api\Entity\Index;
use Drupal\taxonomy\TermInterface;

/**
 * Creates the How We Work page from the static prototype.
 */
function do_base_deploy_populate_how_we_work_page(): string {
  $node_uuid = '74fcf1d0-245c-4027-acee-5ef95bca9912';

  $entity_type_manager = \Drupal::entityTypeManager();
  $node_storage = $entity_type_manager->getStorage('node');

  // Idempotency guard: the page is created once per environment.
  $existing = $node_storage->loadByProperties(['uuid' => $node_uuid]);

  if ($existing) {
    return 'The How We Work page already exists.';
  }

  $paragraph_storage = $entity_type_manager->getStorage('paragraph');

  $component = function (string $type, array $fields) use ($paragraph_storage): array {
    $paragraph = $paragraph_storage->create(['type' => $type] + $fields);

    if (!$paragraph instanceof ParagraphInterface) {
      throw new \RuntimeException(sprintf('Failed to create a "%s" paragraph.', $type));
    }

    $paragraph->save();

    return ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  };

  $rich_text = (fn(string $html): array => ['value' => $html, 'format' => 'civictheme_rich_text']);

  // A failed save mid-way must not leave orphaned paragraphs behind, so the
  // whole assembly commits or rolls back as one unit.
  $transaction = \Drupal::database()->startTransaction();

  try {
    $banner_content = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="ct-text-large">From the first conversation to go-live, here is what happens at each step, what you receive along the way, and how we arrive at a'
        . ' price with nothing hidden. So you know what you are getting into before you commit a dollar.</p>'
        . '<p><a class="ct-button ct-theme-light ct-theme-dark ct-button--secondary ct-button--regular" href="#journey"><strong>See the journey</strong></a> '
        . '<a class="ct-button ct-theme-light ct-theme-dark ct-button--primary ct-button--regular" href="/contact"><strong>Start with a free assessment</strong></a></p>'
      ),
      'field_c_p_theme' => 'dark',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'bottom',
    ]);

    $intro = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">Why this page exists</p>'
        . '<h2 class="text-align-center"><strong>The stressful part is rarely the build.</strong></h2>'
        . '<p class="text-align-center ct-text-large">It is the moment before you commit, when you are handed a single number and asked to trust it, with very little'
        . ' shown above it. Where did it come from? What are you actually paying for? And what happens if the hard parts were guessed wrong?</p>'
        . '<p class="text-align-center ct-text-large">We built our whole process to answer those questions before you have to ask them. Here is exactly what working'
        . ' with us looks like, step by step, and what lands in your hands at each one.</p>'
      ),
      'field_c_p_theme' => 'light',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $journey_steps = [
      [
        'title' => 'The first conversation',
        'body' => 'We listen and map what you actually need, which is often a little different from the initial brief. We do not price anything yet, because we do not know enough yet.',
        'receive' => 'a written summary of your goals and scope, confirmed with you before any number exists.',
      ],
      [
        'title' => 'A proper assessment',
        'body' => 'We review your real platform, the code, the integrations, and the risks, not just the description of it. We name the class of each issue, so it reads as a clear picture rather than a scare document.',
        'receive' => 'a clear, honest report you can act on and share. For a straightforward review, this is often free.',
      ],
      [
        'title' => 'A transparent quotation',
        'body' => 'We price the work from a standard rate card, line by line, adding a buffer only where there are genuine unknowns. One rate, applied the same way for every client.',
        'receive' => 'two complete options, hand-built and AI-assisted, with the hours shown and nothing hidden.',
      ],
      [
        'title' => 'Your approval',
        'body' => 'You review the scope, the timeline, and the price, with a validity window and no pressure. Nothing begins until you have approved the shape of the work.',
        'receive' => 'a fixed, agreed number before a single line of code, and a plan you have signed off.',
      ],
      [
        'title' => 'The build',
        'body' => 'We work in short sprints with a steady rhythm you can follow, testing from day one rather than leaving it to the end. The tooling that keeps us fast is open source, so it can be inspected.',
        'receive' => 'a weekly update on hours and budget, and working software you can watch take shape.',
      ],
      [
        'title' => 'Go-live and handover',
        'body' => 'We handle the release, the go-live, and the handover to your team, so nothing is left dangling once we step back.',
        'receive' => 'a platform your team can actually run, with documentation, not just something that works the day we leave.',
      ],
    ];

    $journey_items = [];

    foreach ($journey_steps as $journey_step) {
      $journey_items[] = $component('steps_item', [
        'field_c_p_title' => $journey_step['title'],
        'field_c_p_summary' => $journey_step['body'],
        'field_p_receive' => $journey_step['receive'],
      ]);
    }

    $journey = $component('steps', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">The journey</p>'
        . '<h2 class="text-align-center" id="journey"><strong>Six steps, and what you get at each one.</strong></h2>'
        . '<p class="text-align-center ct-text-large">Every engagement follows the same path. Each step builds on the one before it, and each leaves you with'
        . ' something concrete in hand.</p>'
      ),
      'field_c_p_list_items' => $journey_items,
      'field_c_p_theme' => 'light',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $no_black_box = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">No black box</p>'
        . '<h2 class="text-align-center"><strong>See how we build the number.</strong></h2>'
        . '<p class="text-align-center ct-text-large">Most agencies keep their pricing hidden. We do the opposite, because a number you can take apart is a number you'
        . ' can trust. Every project is assembled from the same rate card, so the price is just the hours and how confident we are in them. To show you what that'
        . ' means in practice, here is a real kind of project priced in full: a mid-market Drupal 9 site moving to Drupal 11.</p>'
      ),
      'field_c_p_theme' => 'dark',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $estimate = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">The estimate</p>'
        . '<h2 class="text-align-center"><strong>Every line, in plain language.</strong></h2>'
        . '<p class="text-align-center ct-text-large">We have grouped the work into plain-English categories rather than internal codes, but the hours and costs are'
        . ' exactly as our process produces them. In this example we price at $200 an hour plus GST. The rate never moves, so the only things that change the price'
        . ' are the hours and the confidence.</p>'
        . '<table class="ct-table ct-table--striped"><caption>What the work takes</caption>'
        . '<thead><tr><th>Work</th><th>Hours</th><th>Confidence</th><th>Cost (ex GST)</th></tr></thead>'
        . '<tbody>'
        . '<tr><td>Technical assessment</td><td>6</td><td>Very high</td><td>$0 (free)</td></tr>'
        . '<tr><td>Project setup onto the existing codebase</td><td>14 &rarr; 21</td><td>Moderate (buffered)</td><td>$4,200</td></tr>'
        . '<tr><td>Feature and template work (19 items, XS to XL)</td><td>84</td><td>Very high</td><td>$16,800</td></tr>'
        . '<tr><td>Module upgrades and standards cleanup</td><td>25</td><td>Very high</td><td>$5,000</td></tr>'
        . '<tr><td>Automated tests (15 scenarios)</td><td>40</td><td>Very high</td><td>$8,000</td></tr>'
        . '<tr><td>Manual integration testing (2 systems)</td><td>16</td><td>Very high</td><td>$3,200</td></tr>'
        . '<tr><td>Go-live</td><td>12</td><td>Very high</td><td>$2,400</td></tr>'
        . '<tr><td>Documentation</td><td>8</td><td>Very high</td><td>$1,600</td></tr>'
        . '<tr><td><strong>Subtotal</strong></td><td><strong>212</strong></td><td></td><td><strong>$41,200</strong></td></tr>'
        . '</tbody></table>'
        . '<p class="ct-text-small">Notice the setup line. It is the only one carrying a buffer. Setting a project up on top of an existing, years-old codebase'
        . ' carries real unknowns, so we rate our confidence as moderate and lift the estimate from 14 hours to 21, openly. Everything else is work we have done many'
        . ' times, so it passes through at face value. Where we are certain, the number stands. Where we are not, you see the buffer instead of a surprise later.</p>'
      ),
      'field_c_p_theme' => 'light',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $work_to_price = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">From the work to the price</p>'
        . '<h2 class="text-align-center"><strong>Two things sit on top, and we show you both.</strong></h2>'
        . '<table class="ct-table ct-table--striped"><caption>How the estimate becomes the quote</caption>'
        . '<tbody>'
        . '<tr><td>Work subtotal (212 hours)</td><td>$41,200</td></tr>'
        . '<tr><td>Project oversight (weekly updates and tracking, +10%)</td><td>$45,320</td></tr>'
        . '<tr><td>Safety net (20%)</td><td>$54,384</td></tr>'
        . '<tr><td><strong>Quoted total (ex GST)</strong></td><td><strong>$54,384</strong></td></tr>'
        . '<tr><td>GST (10%)</td><td>$59,822</td></tr>'
        . '<tr><td><strong>Total (inc GST)</strong></td><td><strong>$59,822</strong></td></tr>'
        . '</tbody></table>'
        . '<p><strong>Project oversight</strong> is the running of the project: the weekly update that tells you where the hours and the budget stand, the tracking,'
        . ' and the escalation of anything that turns into a risk.</p>'
        . '<p>The <strong>safety net</strong> is there to protect you as much as us. It is twenty percent, and it is not a markup on the work. It is what lets us hold'
        . ' a fixed price when a project turns up a surprise, the cover for a warranty when something needs a second look after go-live, and the cushion that keeps us'
        . ' a stable business, still here to support you in a year. On a fixed-price project we carry that risk, not you, and the safety net is what makes that'
        . ' promise real. Then GST on top, because we are an Australian business. That is the entire path from hours to the figure on the quote. Nothing else is'
        . ' hiding in there.</p>'
      ),
      'field_c_p_theme' => 'light',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $two_options = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">This is what you would receive</p>'
        . '<h2 class="text-align-center"><strong>Two complete options. You choose.</strong></h2>'
        . '<p class="text-align-center ct-text-large">We can build a project by hand, or AI-assisted, using the testing and review harness we developed ourselves.'
        . ' The rate does not change, so the difference is entirely in the hours, and only on the work that genuinely compresses: the development, the migrations,'
        . ' the automated tests. The assessment, the testing, and the coordination do not move, so we do not pretend they do.</p>'
        . '<table class="ct-table ct-table--striped"><caption>The same project, two ways</caption>'
        . '<thead><tr><th>&nbsp;</th><th>Hand-built</th><th>AI-assisted</th></tr></thead>'
        . '<tbody>'
        . '<tr><td>Hours</td><td>212</td><td>146</td></tr>'
        . '<tr><td>Total (ex GST)</td><td>$54,384</td><td>$36,960</td></tr>'
        . '<tr><td><strong>Total (inc GST)</strong></td><td><strong>$59,822</strong></td><td><strong>$40,656</strong></td></tr>'
        . '</tbody></table>'
        . '<p class="ct-text-small">On this project the AI-assisted path lands about 32 percent below the hand-built one, because a migration is development-heavy'
        . ' and a lot of it compresses. Same rate, same automated tests, same review, same quality bar, roughly a third fewer hours. Every change is reviewed and'
        . ' every build is tested before it ships. You choose the trade-off up front, and we never quietly put your work through the harness to pad the bill.</p>'
      ),
      'field_c_p_theme' => 'light',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $reassurance_cards = [
      [
        'title' => 'One rate, always',
        'summary' => 'The same hourly rate for every line and every client. It never moves, so the only things that change your price are the hours and how sure we are of them.',
      ],
      [
        'title' => 'We show the risk',
        'summary' => 'Where there are genuine unknowns, you see the buffer openly instead of a change request later. Pretending to be certain is how fixed prices go wrong.',
      ],
      [
        'title' => 'We check our own work',
        'summary' => 'After a project we compare what we quoted against what it took, line by line, and feed the gaps back into the next rate card. The estimates get more honest over time.',
      ],
    ];

    $reassurance_items = [];

    foreach ($reassurance_cards as $reassurance_card) {
      $reassurance_items[] = $component('civictheme_promo_card', [
        'field_c_p_title' => $reassurance_card['title'],
        'field_c_p_summary' => $reassurance_card['summary'],
        'field_c_p_theme' => 'light',
      ]);
    }

    $reassurance = $component('civictheme_manual_list', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">What holds it together</p>'
        . '<h2 class="text-align-center"><strong>The habits that keep it honest.</strong></h2>'
      ),
      'field_c_p_list_items' => $reassurance_items,
      'field_c_p_list_column_count' => 3,
      'field_c_p_list_fill_width' => FALSE,
      'field_p_list_layout' => 'grid',
      'field_c_p_theme' => 'light',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $honesty = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">The point of all this</p>'
        . '<h2 class="text-align-center"><strong>A quote should never be a mystery.</strong></h2>'
        . '<p class="text-align-center ct-text-large">When you can see how the number was built, from the first conversation through the assessment, the line items,'
        . ' the confidence calls, the safety net, and the two ways to deliver it, you can trust it. And just as importantly, you can hold us to it.</p>'
      ),
      'field_c_p_theme' => 'dark',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $final_cta = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<h2 class="text-align-center"><strong>See what your project would take.</strong></h2>'
        . '<p class="text-align-center ct-text-large">Send us your site and a little about what you need, and we will start with a free assessment. No commitment,'
        . ' just a clear picture of where your platform stands and what the work would involve.</p>'
        . '<p class="text-align-center"><a class="ct-button ct-theme-light ct-theme-dark ct-button--primary ct-button--regular" href="/contact"><strong>Start with a'
        . ' free assessment</strong></a></p>'
        . '<p class="text-align-center ct-text-large"><a href="mailto:info@drevops.com">info@drevops.com</a></p>'
      ),
      'field_c_p_theme' => 'dark',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $values = [
      'type' => 'civictheme_page',
      'uuid' => $node_uuid,
      'title' => 'How We Work',
      'status' => 1,
      'moderation_state' => 'published',
      'field_c_n_summary' => 'See exactly what working with DrevOps looks like, from the first conversation to go-live: what happens at each step, what you receive,'
        . ' and how we build a price with nothing hidden.',
      'field_c_n_banner_theme' => 'dark',
      'field_c_n_banner_type' => 'large',
      'field_c_n_banner_title' => 'Know exactly what working with us looks like.',
      'field_c_n_banner_blend_mode' => 'normal',
      'field_c_n_banner_hide_breadcrumb' => FALSE,
      'field_c_n_banner_components' => [$banner_content],
      'field_c_n_hide_sidebar' => TRUE,
      'field_c_n_show_last_updated' => FALSE,
      'field_c_n_vertical_spacing' => 'none',
      'field_c_n_components' => [
        $intro,
        $journey,
        $no_black_box,
        $estimate,
        $work_to_price,
        $two_options,
        $reassurance,
        $honesty,
        $final_cta,
      ],
    ];

    // The homepage banner media is reused; the page still works without it.
    $banner_media = $entity_type_manager->getStorage('media')->loadByProperties(['uuid' => 'f23d0cc6-1581-4a1b-88c7-a60a8a6e9bc4']);
    $banner_media = reset($banner_media);

    if ($banner_media instanceof MediaInterface) {
      $values['field_c_n_banner_background'] = ['target_id' => $banner_media->id()];
    }

    $node = $node_storage->create($values);
    $node->save();
  }
  catch (\Throwable $throwable) {
    $transaction->rollBack();

    throw $throwable;
  }

  return sprintf('Created the How We Work page (node %s).', $node->id());
}

/**
 * Creates the Our Work page and puts it first in the primary navigation.
 */
function do_base_deploy_populate_our_work_page(): string {
  $node_uuid = '1287abe9-edc6-4cc9-a078-f7261a6c6e1d';
  $menu_name = 'civictheme-primary-navigation';
  $link_title = 'Our work';

  $existing = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $node_uuid]);
  $node = reset($existing);

  if ($node instanceof NodeInterface) {
    Helper::reporter()->skipped('The Our Work page already exists.');
  }
  else {
    $node = _do_base_our_work_build_page($node_uuid);
    Helper::reporter()->created(sprintf('Created the Our Work page (node %s).', $node->id()));
  }

  $link = Helper::menu()->findItem($menu_name, ['title' => $link_title]);

  if ($link instanceof MenuLinkContentInterface) {
    Helper::reporter()->skipped(sprintf('The "%s" link already exists in the primary navigation.', $link_title));

    return Helper::report();
  }

  // createTree() numbers links from their position in the tree it is handed,
  // which describes this link alone and so says nothing about where it belongs
  // among the links already in the menu. Reading the weight first keeps the
  // link being created out of its own calculation.
  $weight = _do_base_menu_leading_weight($menu_name);

  Helper::menu()->createTree($menu_name, [$link_title => 'entity:node/' . $node->id()]);
  Helper::menu()->updateItem($menu_name, ['title' => $link_title], ['weight' => $weight]);

  return Helper::report();
}

/**
 * Regenerates project aliases from the pathauto pattern.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every alias is regenerated, or NULL while batching.
 */
function do_base_deploy_move_projects_to_work(?array &$sandbox = NULL): ?string {
  // The query doubles as the idempotency guard: an alias regenerated onto the
  // new prefix no longer matches, so a repeat deployment finds nothing to do.
  $query = \Drupal::entityQuery('path_alias')->condition('alias', '/projects/%', 'LIKE');

  return Helper::entity($sandbox)->batchQuery($query, static function (PathAliasInterface $alias): void {
    _do_base_project_realias($alias);
  }, status: Reporter::UPDATED);
}

/**
 * Rebuilds the XML sitemap.
 */
function do_base_deploy_rebuild_xmlsitemap(): string {
  // Runs after config import, which is what installs xmlsitemap. A freshly
  // installed xmlsitemap has an empty link table, so without this the site
  // would serve an empty /sitemap.xml until the next cron run.
  if (!\Drupal::moduleHandler()->moduleExists('xmlsitemap')) {
    Helper::reporter()->skipped('The "xmlsitemap" module is not installed.');

    return Helper::report();
  }

  $rebuild_types = xmlsitemap_get_rebuildable_link_types();

  if ($rebuild_types === []) {
    Helper::reporter()->skipped('No XML sitemap link types are rebuildable.');

    return Helper::report();
  }

  // Deploy hooks are themselves executed inside a batch, so these operations
  // are appended to the running batch rather than started as one of their own.
  batch_set(xmlsitemap_rebuild_batch($rebuild_types, TRUE));

  Helper::reporter()->updated('Queued the XML sitemap rebuild.');

  return Helper::report();
}

/**
 * Rebuilds the generated theme colour stylesheet.
 */
function do_base_deploy_refresh_theme_colors(): string {
  /** @var \Drupal\civictheme\CivicthemeColorManager $color_manager */
  $color_manager = \Drupal::classResolver(CivicthemeColorManager::class);

  // The stylesheet is written only while it is missing, so colours reaching
  // the site as configuration are never painted into it. Deleting it leaves
  // the next request to rebuild it from the values the site actually holds.
  $color_manager->invalidateCache();

  Helper::reporter()->updated('Purged the generated theme colour stylesheet.');

  return Helper::report();
}

/**
 * Moves blog articles onto the Blog post content type.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every article is migrated, or NULL while batching.
 */
function do_base_deploy_migrate_blog_posts(?array &$sandbox = NULL): ?string {
  $term = _do_base_blog_topic_term();

  if (!$term instanceof TermInterface) {
    Helper::reporter()->skipped('The "Blog" topic term does not exist, so there is nothing to migrate.');

    return Helper::report();
  }

  // The query doubles as the idempotency guard: once an article is on the blog
  // bundle it no longer matches, so a repeat deployment finds nothing to do.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'civictheme_page')
    ->condition('field_c_n_topics', $term->id());

  // Each article rewrites its row in more than forty field tables, so the batch
  // is kept well below the helper's default to keep any single request short.
  return Helper::entity($sandbox, 10)->batchQuery($query, static function (NodeInterface $node): void {
    _do_base_blog_migrate_node($node);
  }, status: Reporter::UPDATED);
}

/**
 * Points automated lists of blog articles at the Blog post content type.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every list is repointed, or NULL while batching.
 */
function do_base_deploy_repoint_blog_lists(?array &$sandbox = NULL): ?string {
  $term = _do_base_blog_topic_term();

  if (!$term instanceof TermInterface) {
    Helper::reporter()->skipped('The "Blog" topic term does not exist, so there is nothing to repoint.');

    return Helper::report();
  }

  // Scoping by the Blog topic rather than by the targeted bundle leaves lists
  // that legitimately point at pages, such as case studies, untouched.
  $query = \Drupal::entityQuery('paragraph')
    ->condition('type', 'civictheme_automated_list')
    ->condition('field_c_p_list_content_type', 'civictheme_page')
    ->condition('field_c_p_list_topics', $term->id());

  return Helper::entity($sandbox)->batchQuery($query, static function (ParagraphInterface $paragraph): void {
    _do_base_blog_repoint_list($paragraph);
  }, status: Reporter::UPDATED);
}

/**
 * Rebuilds usage tracking for services now that they are pages.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every project is retracked, or NULL while batching.
 */
function do_base_deploy_retrack_service_usage(?array &$sandbox = NULL): ?string {
  // Every project is walked, not only those still holding a reference: a
  // project whose references were all dropped is exactly the one whose
  // recorded usage now names terms that no longer exist.
  $query = \Drupal::entityQuery('node')->condition('type', 'project');

  return Helper::entity($sandbox)->batchQuery($query, static function (NodeInterface $node): void {
    // The migration rewrote the stored ids without saving a node, so nothing
    // told entity_usage that these references now point at pages. Asking the
    // module to recompute the field keeps its own bookkeeping authoritative
    // rather than writing rows on its behalf.
    \Drupal::service(RecreateTrackingDataForFieldQueuer::class)
      ->processRecord('node', (string) $node->id(), (string) $node->getRevisionId(), 'entity_reference', 'field_do_n_services');
  }, status: Reporter::UPDATED);
}

/**
 * Seeds the Sector and Technology vocabularies.
 */
function do_base_deploy_seed_project_vocabularies(): string {
  $trees = [
    'do_sector' => [
      'Federal government',
      'State government',
      'Local government',
      'Higher education',
      'Health',
      'Not-for-profit',
      'Commercial',
    ],
    'do_technology' => [
      'Drupal',
      'PHP',
      'JavaScript',
      'Docker',
      'Kubernetes',
      'AWS',
      'Lagoon',
      'Acquia Cloud',
      'GitHub Actions',
      'CircleCI',
      'Terraform',
      'Behat',
      'PHPUnit',
    ],
  ];

  foreach ($trees as $vocabulary => $tree) {
    // These are a starting point rather than a closed set, so the default safe
    // mode is used: terms an author adds later survive the next deployment.
    Helper::term()->createTree($vocabulary, $tree);
  }

  return Helper::report();
}

/**
 * Regenerates blog aliases that do not follow the pathauto pattern.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every alias is regenerated, or NULL while batching.
 */
function do_base_deploy_realias_blog_posts(?array &$sandbox = NULL): ?string {
  $nids = \Drupal::entityQuery('node')->condition('type', 'blog')->accessCheck(FALSE)->execute();

  if (empty($nids)) {
    Helper::reporter()->skipped('There are no blog posts.');

    return Helper::report();
  }

  // The query doubles as the idempotency guard: an alias regenerated onto the
  // pattern no longer matches, so a repeat deployment finds nothing to do.
  $query = \Drupal::entityQuery('path_alias')
    ->condition('alias', '/blog/%', 'NOT LIKE')
    ->condition('path', array_map(static fn(string $nid): string => '/node/' . $nid, $nids), 'IN');

  return Helper::entity($sandbox)->batchQuery($query, static function (PathAliasInterface $alias): void {
    _do_base_blog_realias($alias);
  }, status: Reporter::UPDATED);
}

/**
 * Sets the search result title and description on the pages that need one.
 *
 * @param array|null $sandbox
 *   Batch sandbox, matching the nullable reference the batch helper takes.
 *
 * @return string|null
 *   Summary once every page is set, or NULL while batching.
 */
function do_base_deploy_set_seo_metatags(?array &$sandbox = NULL): ?string {
  $overrides = _do_base_seo_metatags();
  $alias_manager = \Drupal::service('path_alias.manager');

  $nids = [];
  foreach (array_keys($overrides) as $alias) {
    // The front page is reached through the site setting rather than an alias.
    $path = $alias === '/' ? (string) \Drupal::config('system.site')->get('page.front') : $alias_manager->getPathByAlias($alias);

    if (preg_match('#^/node/(\d+)$#', $path, $matches)) {
      $nids[(int) $matches[1]] = $alias;
    }
  }

  if (empty($nids)) {
    Helper::reporter()->skipped('None of the pages carrying an override exist.');

    return Helper::report();
  }

  $query = \Drupal::entityQuery('node')->condition('nid', array_keys($nids), 'IN');

  return Helper::entity($sandbox, 10)->batchQuery($query, static function (NodeInterface $node) use ($nids, $overrides): void {
    $alias = $nids[(int) $node->id()] ?? '';
    _do_base_set_seo_metatags($node, $overrides[$alias] ?? []);
  });
}

/**
 * Assembles the Our Work page.
 */
function _do_base_our_work_build_page(string $node_uuid): NodeInterface {
  $entity_type_manager = \Drupal::entityTypeManager();
  $paragraph_storage = $entity_type_manager->getStorage('paragraph');

  $component = function (string $type, array $fields) use ($paragraph_storage): array {
    $paragraph = $paragraph_storage->create(['type' => $type] + $fields);

    if (!$paragraph instanceof ParagraphInterface) {
      throw new \RuntimeException(sprintf('Failed to create a "%s" paragraph.', $type));
    }

    $paragraph->save();

    return ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  };

  $rich_text = (fn(string $html): array => ['value' => $html, 'format' => 'civictheme_rich_text']);

  // A failed save mid-way must not leave orphaned paragraphs behind, so the
  // whole assembly commits or rolls back as one unit.
  $transaction = \Drupal::database()->startTransaction();

  try {
    $banner_content = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="ct-text-large">Every project here is a platform we designed, built, upgraded or rescued. Each one names the client where we are free to, the'
        . ' year, the sector and the technologies, along with the part we actually played. No case study gloss, no invented metrics.</p>'
        . '<p><a class="ct-button ct-theme-light ct-theme-dark ct-button--primary ct-button--regular" href="/contact"><strong>Talk to us about your'
        . ' platform</strong></a></p>'
      ),
      'field_c_p_theme' => 'dark',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'bottom',
    ]);

    $intro = $component('civictheme_content', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">What you are looking at</p>'
        . '<h2 class="text-align-center"><strong>The work, not the pitch.</strong></h2>'
        . '<p class="text-align-center ct-text-large">Most portfolios are written to impress. This one is written so you can check it. Every entry names the client,'
        . ' the year, the sector and the technologies involved, along with the part we actually played, which is sometimes the whole platform and sometimes one'
        . ' difficult piece of it.</p>'
        . '<p class="text-align-center ct-text-large">Where a project produced something open source, the contributions are linked from its page, so you can read the'
        . ' code instead of taking our word for it. Where a site is public, the link goes straight to it.</p>'
      ),
      'field_c_p_theme' => 'light',
      'field_c_p_background' => TRUE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $projects = $component('civictheme_automated_list', [
      'field_c_p_content' => $rich_text(
        '<p class="text-align-center eyebrow">Every project</p>'
        . '<h2 class="text-align-center"><strong>Newest work first.</strong></h2>'
      ),
      'field_c_p_list_type' => 'civictheme_automated_list__block1',
      'field_c_p_list_content_type' => 'project',
      'field_c_p_list_limit_type' => 'unlimited',
      'field_c_p_list_limit' => 12,
      'field_c_p_list_item_view_as' => 'civictheme_promo_card',
      'field_c_p_list_item_theme' => 'light',
      'field_c_p_list_column_count' => 3,
      'field_c_p_list_fill_width' => FALSE,
      'field_c_p_theme' => 'light',
      'field_c_p_background' => FALSE,
      'field_c_p_vertical_spacing' => 'both',
    ]);

    $values = [
      'type' => 'civictheme_page',
      'uuid' => $node_uuid,
      'title' => 'Our work',
      'status' => 1,
      'moderation_state' => 'published',
      'field_c_n_summary' => 'Platforms we have designed, built, upgraded and kept running. Each project names the client, the year, the sector and the technologies,'
        . ' along with the part we actually played.',
      'field_c_n_banner_theme' => 'dark',
      'field_c_n_banner_type' => 'large',
      'field_c_n_banner_title' => 'Work you can go and look at.',
      'field_c_n_banner_blend_mode' => 'soft-light',
      'field_c_n_banner_hide_breadcrumb' => FALSE,
      'field_c_n_banner_components' => [$banner_content],
      'field_c_n_hide_sidebar' => TRUE,
      'field_c_n_show_last_updated' => FALSE,
      'field_c_n_vertical_spacing' => 'none',
      'field_c_n_components' => [$intro, $projects],
      // The alias is shorter than the title, so pathauto is switched off for
      // this node rather than left to derive one; without the skip it would
      // take the alias back the first time an author saves the page.
      'path' => ['alias' => '/work', 'pathauto' => PathautoState::SKIP],
    ];

    // A missing image leaves the banner a flat dark band, which is a far
    // smaller loss than a deployment that stops.
    $banner_media = _do_base_our_work_banner_media();

    if ($banner_media instanceof MediaInterface) {
      $values['field_c_n_banner_background'] = ['target_id' => $banner_media->id()];
    }

    $node = $entity_type_manager->getStorage('node')->create($values);
    $node->save();
  }
  catch (\Throwable $throwable) {
    $transaction->rollBack();

    throw $throwable;
  }

  return $node;
}

/**
 * Loads or creates the media entity holding the Our Work banner image.
 */
function _do_base_our_work_banner_media(): ?MediaInterface {
  $media_uuid = 'a93f1457-90ca-4ce0-8685-38f32322317f';
  $media_storage = \Drupal::entityTypeManager()->getStorage('media');

  $existing = $media_storage->loadByProperties(['uuid' => $media_uuid]);
  $media = reset($existing);

  if ($media instanceof MediaInterface) {
    return $media;
  }

  $source = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('do_base') . '/assets/our-work-banner.jpg';

  if (!is_file($source)) {
    return NULL;
  }

  $directory = 'public://images';

  if (!\Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
    return NULL;
  }

  $file = \Drupal::service('file.repository')->writeData((string) file_get_contents($source), $directory . '/our-work-banner.jpg', FileExists::Replace);

  if (!$file instanceof FileInterface) {
    return NULL;
  }

  // CivicTheme image media carry the alt text as their library name, so an
  // author browsing the library sees what the image shows.
  $description = 'Layered slabs of teal glass and brushed metal bars against a soft grey-blue background.';

  $media = $media_storage->create([
    'bundle' => 'civictheme_image',
    'uuid' => $media_uuid,
    'name' => $description,
    'status' => 1,
    'field_c_m_image' => ['target_id' => $file->id(), 'alt' => $description],
  ]);
  $media->save();

  return $media;
}

/**
 * Returns a weight that sorts ahead of every top-level link in a menu.
 */
function _do_base_menu_leading_weight(string $menu_name): int {
  $links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => $menu_name]);

  // Seeded so that a menu with no top-level links still yields a weight.
  $weights = [0];

  foreach ($links as $link) {
    if ($link instanceof MenuLinkContentInterface && $link->getParentId() === '') {
      $weights[] = $link->getWeight();
    }
  }

  return min($weights) - 1;
}

/**
 * Writes a title and description onto a node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node to write to.
 * @param array $tags
 *   Metatag values keyed by tag name.
 */
function _do_base_set_seo_metatags(NodeInterface $node, array $tags): void {
  if (empty($tags) || !$node->hasField('field_n_metatags')) {
    return;
  }

  $current = Json::decode((string) $node->get('field_n_metatags')->value) ?: [];
  $changed = FALSE;

  foreach ($tags as $tag => $value) {
    // Wording already inside the length a result shows is left alone, whoever
    // wrote it. That also makes a repeat deployment a no-op, since everything
    // written here is inside those bounds.
    if (isset($current[$tag]) && _do_base_seo_metatag_fits($tag, (string) $current[$tag], $node)) {
      continue;
    }

    $current[$tag] = $value;
    $changed = TRUE;
  }

  if (!$changed) {
    return;
  }

  $node->set('field_n_metatags', Json::encode($current));
  $node->setNewRevision(FALSE);
  $node->save();

  Helper::reporter()->updated(sprintf('Set the search result wording on "%s".', $node->getTitle()));
}

/**
 * Reports whether a tag value fits what a search result shows.
 *
 * @param string $tag
 *   The metatag name.
 * @param string $value
 *   The value, which may still carry tokens.
 * @param \Drupal\node\NodeInterface $node
 *   The node the value belongs to, giving its tokens something to resolve
 *   against. A value is measured rendered, because that is the length a
 *   search result has to fit.
 *
 * @return bool
 *   TRUE when the value needs no replacing.
 */
function _do_base_seo_metatag_fits(string $tag, string $value, NodeInterface $node): bool {
  // Plain, because a search result shows characters rather than markup: an
  // escaped ampersand would otherwise count as five.
  $length = mb_strlen(\Drupal::token()->replacePlain($value, ['node' => $node], ['clear' => TRUE]));

  // The lengths a search result shows before it cuts the value off.
  return match ($tag) {
    'title' => $length >= 30 && $length <= 60,
    'description' => $length >= 70 && $length <= 155,
    default => TRUE,
  };
}

/**
 * Returns the title and description to publish for each page, keyed by alias.
 *
 * The title is what a search result shows rather than what the page displays,
 * so it carries the qualifiers a heading does not need. The description is
 * written to sit inside the length a result will show.
 *
 * @return array<string, array<string, string>>
 *   Metatag values keyed by tag name, keyed by path alias.
 */
function _do_base_seo_metatags(): array {
  return [
    '/' => [
      'description' => 'Australian Drupal and DevOps consultancy. We build and support Drupal platforms with senior engineering and automated testing from the first commit.',
    ],
    '/about-us' => [
      'title' => 'About Us: Australian Drupal and DevOps Team | [site:name]',
      'description' => 'An Australian Drupal and DevOps consultancy since 2016, working with government, education and enterprise teams who need real engineering.',
    ],
    '/ai-assisted-delivery' => [
      'description' => 'Already on Drupal? We cost the same senior, fully-tested work two ways, by hand and AI-assisted, so you see the difference before you change.',
    ],
    '/ai-integration-automation' => [
      'description' => 'AI integration and automation for Drupal platforms, built with the same testing and review gates as everything else we ship.',
    ],
    '/blog' => [
      'title' => 'Drupal and DevOps Blog | [site:name]',
      'description' => 'Real-world Drupal and DevOps practice from the DrevOps team: technical deep-dives, release notes, and what we have learned running open-source tools.',
    ],
    '/blog/ai-written-code-safe-what-we-put-around-it-it-ships' => [
      'description' => 'Is AI-written code safe? AI speeds up the writing, never the checking. Every change is still reviewed, tested and gated before it ships.',
    ],
    '/blog/are-ai-restrictions-actually-growing-open-source' => [
      'description' => 'AI policies meant to limit AI on private code have an unexpected effect: developers extract generic logic into public modules, and open source grows.',
    ],
    '/blog/becoming-maintainers-drupal-driver-and-drupal-extension-projects' => [
      'description' => 'Alex Skrypnyk now co-maintains Drupal Driver and Drupal Extension. Three coordinated alphas of the Drupal Behat stack just shipped.',
    ],
    '/blog/how-we-upgrade-and-modernise-drupal-sites' => [
      'description' => 'Off Drupal 7, or stuck on 9 or 10? How we upgrade: assess, modernise with Rector, migrate content, and prove it with automated tests.',
    ],
    '/blog/same-senior-drupal-work-about-third-less' => [
      'description' => 'The same senior, tested Drupal work for about a third less on suitable work, because delivery got faster, not because corners were cut.',
    ],
    '/blog/vortex-134' => [
      'description' => 'Vortex 1.34.0 (Flux) adds Composer security audits, Composer Patches v2, modernised dev dependencies, and moves end-to-end tests to PHPUnit.',
    ],
    '/blog/vortex-135-released' => [
      'description' => 'Vortex 1.35 adds full Drupal 11.3 compatibility and updates container images, GitHub Actions and automation tooling for stability and security.',
    ],
    '/blog/vortex-136-released' => [
      'description' => 'Vortex 1.36 makes the template AI-native and migration-ready, with structured agent support, CI-testable migrations and full-stack JS testing.',
    ],
    '/blog/vortex-137-released' => [
      'description' => 'Vortex 1.37 extracts demo code into its own module, splits linting into a standalone CI job, and restructures the CircleCI config for maintenance.',
    ],
    '/blog/vortex-1390-photon-whats-release' => [
      'description' => 'Vortex 1.39.0 adds opt-in visual regression testing, accessibility checks in the box, faster CI, and a reliability fix for Lagoon projects.',
    ],
    '/blog/vortex-1-40-0-spectrum' => [
      'description' => 'Vortex 1.40.0 ships CI secret and workflow scanning, test results on your PRs, site-wide accessibility checks and SDC tooling out of the box.',
    ],
    '/blog/vortex-138-released' => [
      'description' => 'Vortex 1.38 brings new testing, default modules, security and deployment improvements, plus a runtime bump. Here is what changed and why.',
    ],
    '/blog/vortex-2570-released' => [
      'description' => 'Vortex 25.7.0 adds Drupal 11.2 support, faster CI, better Docker and Composer handling for local development, and a more robust installer.',
    ],
    '/blog/vortex-2580-released' => [
      'description' => 'Vortex 25.8.0 adds Drupal CMS support, cuts CI build times by up to 5 minutes, improves Lagoon hosting, and significantly enhances the installer.',
    ],
    '/blog/what-ai-actually-doing-open-source' => [
      'description' => 'From maintaining around 40 repos: AI speeds up consuming open source far faster than contributing back, and that gap is the real threat.',
    ],
    '/contact' => [
      'title' => 'Contact Our Drupal Team | [site:name]',
      'description' => 'Talk to the engineers who would do the work. Tell us what you are running and what is not working, and we will tell you what we would do.',
    ],
    '/how-we-work' => [
      'title' => 'How We Work: Process, Pricing, Delivery | [site:name]',
      'description' => 'What working with DrevOps looks like, from first conversation to go-live: what happens at each step and how we build a price with nothing hidden.',
    ],
    '/responsible-ai' => [
      'title' => 'Responsible AI Policy | [site:name]',
      'description' => 'How DrevOps uses AI responsibly: safeguards around data security, governance and human oversight, so nothing you share trains a public model.',
    ],
    '/services' => [
      'title' => 'Drupal and DevOps Services | [site:name]',
      'description' => 'Expert-led Drupal and DevOps services: builds, upgrades, migrations, automated testing, CI/CD and ongoing support for platforms that matter.',
    ],
    '/services/audit' => [
      'title' => 'Drupal Site Audit and Health Check | [site:name]',
    ],
    '/services/automated-testing' => [
      'title' => 'Automated Testing for Drupal | [site:name]',
    ],
    '/services/ci-cd-pipelines' => [
      'description' => 'Automated CI/CD pipelines that test, build and deploy your Drupal application safely, using GitHub Actions, CircleCI, Azure Pipelines or GitLab CI.',
    ],
    '/services/civictheme' => [
      'title' => 'CivicTheme Design System Development | [site:name]',
    ],
    '/services/containerisation' => [
      'title' => 'Docker Containerisation for Drupal | [site:name]',
    ],
    '/services/drupal-upgrades' => [
      'title' => 'Drupal Core and Contrib Upgrades | [site:name]',
    ],
    '/services/govcms' => [
      'title' => 'GovCMS Development and Migration | [site:name]',
    ],
    '/services/migrations' => [
      'title' => 'Drupal Migrations from Any CMS | [site:name]',
    ],
    '/services/support-plans' => [
      'title' => 'SLA-backed Drupal Support Plans | [site:name]',
    ],
    '/work' => [
      'description' => 'Drupal projects we have delivered and supported, from government platforms to community organisations, plus the open-source tools that came out of it.',
    ],
  ];
}

/**
 * Regenerates the alias of the blog post a path alias points at.
 */
function _do_base_blog_realias(PathAliasInterface $alias): void {
  if (!preg_match('#^/node/(\d+)$#', $alias->getPath(), $matches)) {
    return;
  }

  $node = \Drupal::entityTypeManager()->getStorage('node')->load($matches[1]);

  if (!$node instanceof NodeInterface || $node->bundle() !== 'blog') {
    return;
  }

  // Regenerating rather than rewriting the alias string keeps the result
  // defined by the pattern alone, and lets the redirect module record the
  // superseded path so inbound links keep resolving.
  \Drupal::service('pathauto.generator')->updateEntityAlias($node, 'update');
}

/**
 * Regenerates the alias of the project a path alias points at.
 */
function _do_base_project_realias(PathAliasInterface $alias): void {
  if (!preg_match('#^/node/(\d+)$#', $alias->getPath(), $matches)) {
    return;
  }

  $node = \Drupal::entityTypeManager()->getStorage('node')->load($matches[1]);

  if (!$node instanceof NodeInterface || $node->bundle() !== 'project') {
    return;
  }

  // Regenerating rather than rewriting the alias string keeps the result
  // defined by the pattern alone, and lets the redirect module record the
  // superseded path so inbound links keep resolving.
  \Drupal::service('pathauto.generator')->updateEntityAlias($node, 'update');
}

/**
 * Loads the taxonomy term that defines which articles are blog articles.
 */
function _do_base_blog_topic_term(): ?TermInterface {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
    'vid' => 'civictheme_topics',
    'name' => 'Blog',
  ]);

  $term = reset($terms);

  return $term instanceof TermInterface ? $term : NULL;
}

/**
 * Moves a single article onto the blog bundle.
 */
function _do_base_blog_migrate_node(NodeInterface $node): void {
  $nid = $node->id();
  $langcodes = array_keys($node->getTranslationLanguages());
  $database = \Drupal::database();

  // Drupal treats a loaded entity's bundle as immutable, and the only
  // entity-API alternative - delete and recreate - would discard the node ID,
  // its revisions and every paragraph reference. An article left half-migrated
  // would be readable through neither bundle, so each one commits or rolls back
  // on its own, which is also what makes an interrupted batch safe to resume.
  $transaction = $database->startTransaction();

  try {
    $database->update('node')->fields(['type' => 'blog'])->condition('nid', $nid)->execute();
    $database->update('node_field_data')->fields(['type' => 'blog'])->condition('nid', $nid)->execute();

    foreach (_do_base_blog_bundle_tables('civictheme_page', 'blog') as $table) {
      $database->update($table)->fields(['bundle' => 'blog'])->condition('entity_id', $nid)->condition('bundle', 'civictheme_page')->execute();
    }

    // The sitemap records each link's bundle alongside it, and the rebuild that
    // would otherwise correct it runs only once per environment.
    if ($database->schema()->tableExists('xmlsitemap')) {
      $database->update('xmlsitemap')->fields(['subtype' => 'blog'])->condition('type', 'node')->condition('id', $nid)->execute();
    }
  }
  catch (\Throwable $throwable) {
    $transaction->rollBack();

    throw $throwable;
  }

  \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
  _do_base_blog_reindex($nid, $langcodes);

  Cache::invalidateTags(['node:' . $nid, 'node_list', 'node_list:blog', 'node_list:civictheme_page']);
}

/**
 * Points a single automated list at the blog bundle, in every revision.
 */
function _do_base_blog_repoint_list(ParagraphInterface $paragraph): void {
  $storage = \Drupal::entityTypeManager()->getStorage('paragraph');

  if (!$storage instanceof SqlContentEntityStorage) {
    throw new \RuntimeException('Paragraph storage is not SQL-backed, so field tables cannot be resolved.');
  }

  $table_mapping = $storage->getTableMapping();

  if (!$table_mapping instanceof DefaultTableMapping) {
    throw new \RuntimeException('Paragraph storage does not use the default table mapping.');
  }

  $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', 'civictheme_automated_list');
  $storage_definition = $definitions['field_c_p_list_content_type']->getFieldStorageDefinition();
  $column = $table_mapping->getFieldColumnName($storage_definition, 'value');
  $pid = $paragraph->id();

  // Saving the paragraph would only rewrite its current revision, while the
  // host node references one specific revision that is not necessarily that
  // one, so the value is set across every revision instead. Older revisions
  // want the new bundle too: left on the old one they render an empty list,
  // because no page carries the Blog topic once the migration has run.
  $candidates = [
    $table_mapping->getDedicatedDataTableName($storage_definition),
    $table_mapping->getDedicatedRevisionTableName($storage_definition),
  ];

  $database = \Drupal::database();

  foreach ($candidates as $candidate) {
    if (!$database->schema()->tableExists($candidate)) {
      continue;
    }

    $database->update($candidate)->fields([$column => 'blog'])->condition('entity_id', $pid)->condition($column, 'civictheme_page')->execute();
  }

  $tags = ['paragraph:' . $pid];
  $parent = $paragraph->getParentEntity();

  if ($parent instanceof NodeInterface) {
    $tags[] = 'node:' . $parent->id();
  }

  $storage->resetCache([$pid]);
  Cache::invalidateTags($tags);
}

/**
 * Lists the node field tables that record the bundle for the given bundles.
 */
function _do_base_blog_bundle_tables(string ...$bundles): array {
  static $cache = [];

  $key = implode(':', $bundles);

  if (isset($cache[$key])) {
    return $cache[$key];
  }

  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!$storage instanceof SqlContentEntityStorage) {
    throw new \RuntimeException('Node storage is not SQL-backed, so field tables cannot be resolved.');
  }

  $table_mapping = $storage->getTableMapping();

  if (!$table_mapping instanceof DefaultTableMapping) {
    throw new \RuntimeException('Node storage does not use the default table mapping.');
  }

  $field_manager = \Drupal::service('entity_field.manager');
  $schema = \Drupal::database()->schema();
  $tables = [];

  // Deriving the list from the storage definitions rather than naming the
  // tables keeps a field added to either bundle later from being missed. The
  // union of both bundles is taken so a field attached to only one of them
  // cannot leave rows stranded under the old bundle.
  foreach ($bundles as $bundle) {
    foreach ($field_manager->getFieldDefinitions('node', $bundle) as $definition) {
      $storage_definition = $definition->getFieldStorageDefinition();

      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        continue;
      }

      $candidates = [
        $table_mapping->getDedicatedDataTableName($storage_definition),
        $table_mapping->getDedicatedRevisionTableName($storage_definition),
      ];

      foreach ($candidates as $candidate) {
        if ($schema->tableExists($candidate)) {
          $tables[$candidate] = $candidate;
        }
      }
    }
  }

  $cache[$key] = $tables;

  return $tables;
}

/**
 * Queues a node for reindexing in each of the given languages.
 */
function _do_base_blog_reindex(int|string $nid, array $langcodes): void {
  if (!\Drupal::moduleHandler()->moduleExists('search_api')) {
    return;
  }

  // Changing the bundle in storage fires no entity hooks, so the indexed
  // document would otherwise keep describing the article as a page.
  $item_ids = array_map(static fn (string $langcode): string => $nid . ':' . $langcode, array_values($langcodes));

  foreach (\Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple() as $entity) {
    if (!$entity instanceof Index) {
      continue;
    }

    if ($entity->isValidDatasource('entity:node')) {
      $entity->trackItemsUpdated('entity:node', $item_ids);
    }
  }
}

/**
 * Gives every topic term a URL alias so topic pages are reachable.
 */
function do_base_deploy_alias_topic_terms(?array &$sandbox = NULL): ?string {
  $query = \Drupal::entityQuery('taxonomy_term')
    ->accessCheck(FALSE)
    ->condition('vid', 'civictheme_topics');

  return Helper::entity($sandbox, 25)->batchQuery($query, static function (TermInterface $term): void {
    _do_base_alias_topic_term($term);
  }, status: Reporter::UPDATED);
}

/**
 * Puts a topic term back under the alias pattern and saves it.
 */
function _do_base_alias_topic_term(TermInterface $term): void {
  if (!$term->hasField('path')) {
    return;
  }

  // Terms created programmatically carry SKIP, which is why most topics have
  // no alias at all. Handing them back to the pattern is what puts them on
  // /topics/<name>, and it keeps them there when an editor renames one.
  $term->set('path', ['pathauto' => PathautoState::CREATE]);
  $term->save();
}

/**
 * Adds a related-content list to the foot of every blog post.
 */
function do_base_deploy_add_related_lists(?array &$sandbox = NULL): ?string {
  $query = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'blog');

  return Helper::entity($sandbox, 10)->batchQuery($query, static function (NodeInterface $node): void {
    _do_base_add_related_list($node);
  }, status: Reporter::UPDATED);
}

/**
 * Appends an Automated list configured to follow the post's own topics.
 */
function _do_base_add_related_list(NodeInterface $node): void {
  if (!$node->hasField('field_c_n_components')) {
    return;
  }

  foreach ($node->get('field_c_n_components')->referencedEntities() as $existing) {
    if ($existing instanceof ParagraphInterface && $existing->bundle() === 'civictheme_automated_list' && !$existing->get('field_c_p_list_topics_from_page')->isEmpty() && (bool) $existing->get('field_c_p_list_topics_from_page')->value) {
      return;
    }
  }

  $paragraph = Paragraph::create([
    'type' => 'civictheme_automated_list',
    'field_c_p_title' => 'Related posts',
    'field_c_p_list_type' => 'civictheme_automated_list__block1',
    'field_c_p_list_content_type' => 'blog',
    'field_c_p_list_limit_type' => 'limited',
    'field_c_p_list_limit' => 3,
    'field_c_p_list_column_count' => 3,
    'field_c_p_list_topics_from_page' => 1,
  ]);
  $paragraph->setParentEntity($node, 'field_c_n_components');
  $paragraph->save();

  $components = $node->get('field_c_n_components')->getValue();
  $components[] = [
    'target_id' => $paragraph->id(),
    'target_revision_id' => $paragraph->getRevisionId(),
  ];

  $node->set('field_c_n_components', $components);
  $node->setNewRevision(FALSE);
  $node->save();
}
