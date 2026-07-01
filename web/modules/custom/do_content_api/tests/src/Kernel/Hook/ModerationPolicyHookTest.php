<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Kernel\Hook;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\do_content_api\Hook\ModerationPolicyHook;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for ModerationPolicyHook.
 */
#[CoversClass(ModerationPolicyHook::class)]
#[Group('do_content_api')]
class ModerationPolicyHookTest extends KernelTestBase {

  use ContentModerationTestTrait;
  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'options',
    'workflows',
    'content_moderation',
    'media',
    'image',
    'file',
    'paragraphs',
    'entity_reference_revisions',
    'serialization',
    'jsonapi',
    'key_auth',
    'subrequests',
    'do_content_api',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter']);

    NodeType::create(['type' => 'civictheme_page', 'name' => 'Page'])->save();
    $this->createMediaType('image', ['id' => 'civictheme_image', 'label' => 'Image']);

    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'civictheme_page');
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'media', 'civictheme_image');
    $workflow->save();

    // Reserve uid 1; the superuser bypasses permission checks, so the test
    // actors created in each case must be regular accounts.
    $this->createUser();
  }

  /**
   * Tests that an API actor's page is forced to draft.
   */
  public function testApiPageForcedToDraft(): void {
    // Prepare.
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    // Act.
    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Page',
      'moderation_state' => 'published',
    ]);
    $node->save();

    // Assert.
    $this->assertSame('draft', $node->get('moderation_state')->value);
    $this->assertFalse($node->isPublished());
  }

  /**
   * Tests that any non-draft state authored through the API is forced to draft.
   */
  public function testApiPageNonDraftForcedToDraft(): void {
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Review page',
      'moderation_state' => 'needs_review',
    ]);
    $node->save();

    $this->assertSame('draft', $node->get('moderation_state')->value);
    $this->assertFalse($node->isPublished());
  }

  /**
   * Tests that an API actor's draft page is left as a draft.
   */
  public function testApiPageDraftUnchanged(): void {
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Draft page',
      'moderation_state' => 'draft',
    ]);
    $node->save();

    $this->assertSame('draft', $node->get('moderation_state')->value);
  }

  /**
   * Tests that an API actor's image is forced to published.
   */
  public function testApiMediaForcedToPublished(): void {
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    $media = Media::create([
      'bundle' => 'civictheme_image',
      'name' => '[TEST] Image',
      'moderation_state' => 'draft',
    ]);
    $media->save();

    $this->assertSame('published', $media->get('moderation_state')->value);
    $this->assertTrue($media->isPublished());
  }

  /**
   * Tests that a non-API actor's page is left untouched.
   */
  public function testNonApiPageUnaffected(): void {
    $editor_user = $this->createUser(['administer nodes']);
    $this->assertNotFalse($editor_user);
    $this->setCurrentUser($editor_user);

    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Editor page',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $this->assertSame('published', $node->get('moderation_state')->value);
    $this->assertTrue($node->isPublished());
  }

  /**
   * Tests that an API actor can publish a page it previously authored.
   */
  public function testApiPagePublishAfterAuthoringIsHonoured(): void {
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    // Authoring (create) is forced to draft by the policy.
    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Authored then published',
      'moderation_state' => 'draft',
    ]);
    $node->save();
    $this->assertSame('draft', $node->get('moderation_state')->value);

    // A later publish is an update, not authoring, so it must be honoured.
    $node->set('moderation_state', 'published');
    $node->save();

    $this->assertSame('published', $node->get('moderation_state')->value);
    $this->assertTrue($node->isPublished());
  }

  /**
   * Tests that a later moderation change to an API-authored media is honoured.
   */
  public function testApiMediaUpdateAfterAuthoringIsHonoured(): void {
    $api_user = $this->createUser(['use content authoring api']);
    $this->assertNotFalse($api_user);
    $this->setCurrentUser($api_user);

    // Authoring (create) forces media to published.
    $media = Media::create([
      'bundle' => 'civictheme_image',
      'name' => '[TEST] Authored then updated',
      'moderation_state' => 'draft',
    ]);
    $media->save();
    $this->assertSame('published', $media->get('moderation_state')->value);

    // A later state change is an update, not authoring, so it is honoured.
    $media->set('moderation_state', 'draft');
    $media->save();

    $this->assertSame('draft', $media->get('moderation_state')->value);
  }

  /**
   * Tests that the superuser is treated as an ordinary administrator.
   */
  public function testSuperuserPageUnaffected(): void {
    // User 1 holds no authoring permission but bypasses every permission
    // check, so without an explicit guard the policy would force its content
    // to draft and silently block an administrator from publishing.
    $superuser = User::load(1);
    $this->assertInstanceOf(User::class, $superuser);
    $this->setCurrentUser($superuser);

    $node = Node::create([
      'type' => 'civictheme_page',
      'title' => '[TEST] Superuser page',
      'moderation_state' => 'published',
    ]);
    $node->save();

    $this->assertSame('published', $node->get('moderation_state')->value);
    $this->assertTrue($node->isPublished());
  }

}
