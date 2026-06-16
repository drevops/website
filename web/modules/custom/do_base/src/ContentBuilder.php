<?php

declare(strict_types=1);

namespace Drupal\do_base;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Builds the CivicTheme content that the deploy hooks install.
 *
 * The deploy hooks assemble each page from these typed builders so the design
 * is expressed through component fields, never stored markup.
 */
final class ContentBuilder {

  /**
   * Populate the node banner so it renders as the page hero.
   *
   * The design's hero is the CivicTheme banner set to the "hero" type (themed
   * in the subtheme), not a content section. This sets the banner title, type
   * and theme. When a subtitle is given it is added as a CivicTheme content
   * component inside the banner (CivicTheme renders
   * `field_c_n_banner_components` into the banner's content slot), optionally
   * with a call-to-action button. Previous banner components are returned for
   * deletion after the save.
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
  public static function stageBanner(Node $node, string $title, string $subtitle = '', ?array $cta = NULL): array {
    if ($node->hasField('field_c_n_banner_title')) {
      $node->set('field_c_n_banner_title', $title);
    }

    if ($node->hasField('field_c_n_banner_type')) {
      $node->set('field_c_n_banner_type', 'hero');
    }

    if ($node->hasField('field_c_n_banner_theme')) {
      $node->set('field_c_n_banner_theme', 'dark');
    }

    // The design hero is the dark hero banner with its atmospheric glow, not an
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
      $intro = self::bannerIntro($subtitle, $cta);
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
  protected static function bannerIntro(string $subtitle, ?array $cta): Paragraph {
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
   * @param string $variant
   *   Optional appearance variant key (e.g. 'itemized'). Empty for none.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   An unsaved snippet paragraph.
   */
  public static function snippet(string $title, string $summary, string $variant = ''): Paragraph {
    $values = [
      'type' => 'civictheme_snippet',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $title,
      'field_c_p_summary' => $summary,
    ];

    if ($variant !== '') {
      $values['field_p_snippet_appearance'] = $variant;
    }

    return Paragraph::create($values);
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
  public static function factCard(string $fact, string $label, string $suffix = ''): Paragraph {
    return Paragraph::create([
      'type' => 'do_fact_card',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $fact,
      'field_p_suffix' => $suffix,
      'field_c_p_summary' => $label,
    ]);
  }

  /**
   * Build a dark CivicTheme manual list of snippet items.
   *
   * Design treatments are driven by per-item variant classes on the snippets
   * themselves; the list carries no style field.
   *
   * @param string $title
   *   The section heading.
   * @param int $columns
   *   Column count (1-4).
   * @param \Drupal\paragraphs\Entity\Paragraph[] $items
   *   The snippet list items.
   * @param string $eyebrow
   *   The small eyebrow label above the heading.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   An unsaved manual list paragraph referencing the saved items.
   */
  public static function manualList(string $title, int $columns, array $items, string $eyebrow = ''): Paragraph {
    foreach ($items as $item) {
      $item->save();
    }

    return Paragraph::create([
      'type' => 'civictheme_manual_list',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $title,
      'field_c_p_list_column_count' => $columns,
      'field_p_eyebrow' => $eyebrow,
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
  public static function callout(string $title, string $body, string $link_title = '', string $link_uri = ''): Paragraph {
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
    // the homepage CTA renders its email inside the body as a plain link
    // instead.
    if ($link_uri !== '') {
      $values['field_c_p_links'] = [['uri' => self::linkUri($link_uri), 'title' => $link_title]];
    }

    return Paragraph::create($values);
  }

  /**
   * Build a Service detail paragraph.
   *
   * @param string $title
   *   The service name.
   * @param string $tagline
   *   The one-line summary shown beside the title.
   * @param string[] $paras
   *   Description paragraphs.
   * @param string[] $includes
   *   The "what's included" list items.
   * @param string $price_label
   *   The price label, e.g. "Typical engagement".
   * @param string $price_value
   *   The price, e.g. "$40K - $180K".
   * @param string $link_title
   *   The call-to-action button text.
   * @param string $link_uri
   *   The call-to-action button URI.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   An unsaved Service detail paragraph.
   */
  public static function serviceDetail(string $title, string $tagline, array $paras, array $includes, string $price_label, string $price_value, string $link_title, string $link_uri): Paragraph {
    $description = '';

    foreach ($paras as $para) {
      $description .= '<p>' . $para . '</p>';
    }

    return Paragraph::create([
      'type' => 'do_service_detail',
      'field_c_p_theme' => 'dark',
      'field_c_p_title' => $title,
      'field_p_tagline' => $tagline,
      'field_c_p_content' => [
        'value' => $description,
        'format' => 'civictheme_rich_text',
      ],
      'field_p_includes' => $includes,
      'field_p_price_label' => $price_label,
      'field_p_price_value' => $price_value,
      'field_c_p_link' => [
        'uri' => self::linkUri($link_uri),
        'title' => $link_title,
      ],
    ]);
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
  public static function contentRichText(string $html): Paragraph {
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
   * Delete entities staged for removal after the referencing node was saved.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities to delete.
   */
  public static function deleteEntities(array $entities): void {
    foreach ($entities as $entity) {
      $entity->delete();
    }
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
  protected static function linkUri(string $uri): string {
    return str_starts_with($uri, '/') ? 'internal:' . $uri : $uri;
  }

  /**
   * Build the rich-text body for contact details and "what to expect" steps.
   *
   * @return string
   *   The rich-text HTML body.
   */
  public static function contactInfo(): string {
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
  public static function blogBody(): string {
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

}
