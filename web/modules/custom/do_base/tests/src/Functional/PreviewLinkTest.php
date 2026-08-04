<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Functional;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\node\NodeInterface;
use Drupal\preview_link\Entity\PreviewLinkInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that a preview link shows unpublished content to its recipient only.
 *
 * The recipient is an anonymous visitor holding nothing but the token, so these
 * tests never authenticate: what a logged-out request can and cannot reach is
 * the whole contract.
 */
#[Group('do_base')]
class PreviewLinkTest extends DoBaseFunctionalTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'workflows',
    'page_cache',
    'preview_link',
    'do_base',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Without a page lifetime every response is uncacheable anyway, which would
    // pass the cache assertions without exercising anything.
    $this->config('system.performance')->set('cache.page.max_age', 900)->save();
    $this->config('preview_link.settings')->set('enabled_entity_types', ['node' => []])->save();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
  }

  /**
   * Tests that the recipient of a link reads content the public cannot.
   */
  public function testRecipientReadsUnpublishedContent(): void {
    // Prepare.
    $node = $this->createModeratedNode('[TEST] Draft Page', 'draft');
    $preview_link = $this->createPreviewLink($node);

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSession()->statusCodeEquals(403);

    // Act.
    $this->drupalGet($preview_link->getUrl($node));

    // Assert.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('[TEST] Draft Page');
  }

  /**
   * Tests that a draft awaiting review is previewed, not the published page.
   *
   * The editorial workflow keeps a draft off the default revision, so a page
   * that is already live still renders its published text everywhere except
   * here. Previewing the pending revision is why this module was chosen.
   */
  public function testPendingDraftIsPreviewedOverThePublishedRevision(): void {
    // Prepare.
    $node = $this->createModeratedNode('[TEST] Published Page', 'published');
    $node->setTitle('[TEST] Pending Draft');
    $node->set('moderation_state', 'draft');
    $node->setNewRevision();
    $node->save();

    $preview_link = $this->createPreviewLink($node);

    // Act.
    $this->drupalGet($preview_link->getUrl($node));

    // Assert.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('[TEST] Pending Draft');
    $this->assertSession()->pageTextNotContains('[TEST] Published Page');
  }

  /**
   * Tests that a link stops working once it expires.
   *
   * The link is opened first so the refusal is known to come from the expiry
   * rather than from a URL that never worked, and the canonical route is
   * checked afterwards because by then the session is holding a stale token.
   */
  public function testExpiredLinkIsRefused(): void {
    // Prepare.
    $node = $this->createModeratedNode('[TEST] Expired Draft', 'draft');
    $preview_link = $this->createPreviewLink($node);
    $url = $preview_link->getUrl($node);

    // Act.
    $this->drupalGet($url);

    // Assert.
    $this->assertSession()->statusCodeEquals(200);

    // Prepare.
    $preview_link->setExpiry(new \DateTime('-1 minute'));
    $preview_link->save();

    // Act.
    $this->drupalGet($url);

    // Assert.
    $this->assertSession()->statusCodeEquals(403);

    // Act.
    $this->drupalGet($node->toUrl());

    // Assert.
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that a link unlocks only the content it was created for.
   *
   * Preview links carry several entities so a page's paragraphs and media
   * travel with it, which makes it worth proving that the session a token
   * opens does not reach unpublished content the link never named.
   */
  public function testLinkDoesNotUnlockUnrelatedContent(): void {
    // Prepare.
    $linked = $this->createModeratedNode('[TEST] Linked Draft', 'draft');
    $unrelated = $this->createModeratedNode('[TEST] Unrelated Draft', 'draft');
    $preview_link = $this->createPreviewLink($linked);

    // Act.
    $this->drupalGet($preview_link->getUrl($linked));

    // Assert.
    $this->assertSession()->statusCodeEquals(200);

    // Act.
    $this->drupalGet($unrelated->toUrl());

    // Assert.
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that a preview page is kept out of search indexes.
   */
  public function testPreviewPageIsNotIndexable(): void {
    // Prepare.
    $node = $this->createModeratedNode('[TEST] Unindexed Draft', 'draft');
    $preview_link = $this->createPreviewLink($node);

    // Act.
    $this->drupalGet($preview_link->getUrl($node));

    // Assert.
    $this->assertSession()->elementExists('css', 'meta[name="robots"][content="noindex, nofollow"]');
  }

  /**
   * Tests that a preview page is never held by a shared cache.
   *
   * A cached copy would outlive the link, so an expired or regenerated token
   * would keep serving the content until the cache entry lapsed.
   */
  public function testPreviewPageIsNotStoredBySharedCaches(): void {
    // Prepare.
    $node = $this->createModeratedNode('[TEST] Uncached Draft', 'draft');
    $preview_link = $this->createPreviewLink($node);
    $published = $this->createModeratedNode('[TEST] Cached Page', 'published');

    // Act.
    $this->drupalGet($published->toUrl());

    // Assert.
    $this->assertStringContainsString('public', (string) $this->getSession()->getResponseHeader('Cache-Control'), 'An ordinary node page is expected to stay publicly cacheable.');

    // Act.
    $this->drupalGet($preview_link->getUrl($node));

    // Assert.
    $cache_control = (string) $this->getSession()->getResponseHeader('Cache-Control');
    $this->assertStringContainsString('private', $cache_control);
    $this->assertStringNotContainsString('public', $cache_control);
  }

  /**
   * Creates a node in the given moderation state.
   */
  protected function createModeratedNode(string $title, string $moderation_state): NodeInterface {
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => $title,
      'moderation_state' => $moderation_state,
    ]);

    return $node;
  }

  /**
   * Creates a preview link covering the given node.
   */
  protected function createPreviewLink(NodeInterface $node): PreviewLinkInterface {
    $preview_link = $this->container->get('entity_type.manager')->getStorage('preview_link')->create(['entities' => [$node]]);
    $preview_link->save();

    return $preview_link;
  }

}
