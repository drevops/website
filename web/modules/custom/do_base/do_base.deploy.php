<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\media\MediaInterface;

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
    $paragraph->save();

    return ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  };

  $rich_text = function (string $html): array {
    return ['value' => $html, 'format' => 'civictheme_rich_text'];
  };

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
