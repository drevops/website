<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the permission set exported for the content API service role.
 *
 * The role backs an externally authenticated account, so both directions of
 * drift matter: a permission silently dropped breaks the client, and a
 * permission silently added widens what a leaked API key can reach.
 */
#[CoversNothing]
#[Group('do_content_api')]
class ServiceRolePermissionsTest extends UnitTestCase {

  /**
   * Every permission the service role is meant to hold, in exported order.
   */
  protected const array EXPECTED_PERMISSIONS = [
    'access content',
    'access content overview',
    'administer nodes',
    'administer redirects',
    'create blog content',
    'create civictheme_alert content',
    'create civictheme_event content',
    'create civictheme_image media',
    'create civictheme_page content',
    'create media',
    'create project content',
    'create url aliases',
    'delete any blog content',
    'delete any civictheme_alert content',
    'delete any civictheme_event content',
    'delete any civictheme_page content',
    'delete any project content',
    'edit any blog content',
    'edit any civictheme_alert content',
    'edit any civictheme_event content',
    'edit any civictheme_page content',
    'edit any project content',
    'edit own blog content',
    'edit own civictheme_alert content',
    'edit own civictheme_event content',
    'edit own civictheme_page content',
    'edit own project content',
    'issue subrequests',
    'use civictheme_editorial transition archive',
    'use civictheme_editorial transition create_new_draft',
    'use civictheme_editorial transition needs_review',
    'use civictheme_editorial transition publish',
    'use civictheme_editorial transition restore',
    'use civictheme_editorial transition restore_to_draft',
    'use civictheme_editorial transition restore_to_needs_review',
    'use civictheme_editorial transition send_back_to_draft',
    'use content authoring api',
    'use key authentication',
    'use text format civictheme_rich_text',
    'view any unpublished content',
    'view latest version',
    'view own unpublished content',
    'view own unpublished media',
  ];

  /**
   * Tests that the exported permissions match the intended set exactly.
   *
   * The assertions below each pin one property of the role and explain why it
   * holds. This one pins the whole set, so a permission granted through the
   * admin UI and exported without review fails the build instead of reaching
   * an environment as a silent widening of an API-key account.
   */
  public function testPermissionsMatchTheIntendedSet(): void {
    // Assert.
    $this->assertSame(self::EXPECTED_PERMISSIONS, $this->loadRole()['permissions']);
  }

  /**
   * Tests that the role carries a content management permission.
   */
  #[DataProvider('dataProviderManagementPermission')]
  public function testManagementPermission(string $permission): void {
    // Assert.
    $this->assertContains($permission, $this->loadRole()['permissions']);
  }

  /**
   * Data provider for testManagementPermission.
   */
  public static function dataProviderManagementPermission(): \Iterator {
    yield 'administer content' => ['administer nodes'];
    yield 'content overview' => ['access content overview'];
    yield 'read unpublished content' => ['view any unpublished content'];
    yield 'read pending revisions' => ['view latest version'];
    yield 'authoring api gate' => ['use content authoring api'];
    yield 'key authentication' => ['use key authentication'];
  }

  /**
   * Tests that every creatable content type is also fully manageable.
   *
   * Adding a content type to the API means adding a create permission, which
   * is easy to do without the matching edit and delete grants. This pins the
   * three together so a partially wired bundle fails here.
   */
  public function testCreatableBundlesAreManageable(): void {
    // Prepare.
    $permissions = $this->loadRole()['permissions'];
    $bundles = [];

    foreach ($permissions as $permission) {
      if (preg_match('/^create (\w+) content$/', (string) $permission, $matches)) {
        $bundles[] = $matches[1];
      }
    }

    // Assert.
    $this->assertNotEmpty($bundles, 'The role grants no content creation at all.');

    foreach ($bundles as $bundle) {
      $this->assertContains('edit any ' . $bundle . ' content', $permissions);
      $this->assertContains('delete any ' . $bundle . ' content', $permissions);
    }
  }

  /**
   * Tests that the role can use every transition of the editorial workflow.
   *
   * Content moderation forbids an update outright when the account holds no
   * transition out of the entity's current state, so a missing transition
   * does not just block a state change - it makes content in that state
   * uneditable while leaving it deletable.
   */
  public function testAllEditorialTransitionsAreGranted(): void {
    // Prepare.
    $workflow = $this->loadConfig('workflows.workflow.civictheme_editorial.yml');
    $transitions = array_keys($workflow['type_settings']['transitions']);
    $permissions = $this->loadRole()['permissions'];

    // Assert.
    $this->assertNotEmpty($transitions, 'The editorial workflow defines no transitions.');

    foreach ($transitions as $transition) {
      $this->assertContains('use civictheme_editorial transition ' . $transition, $permissions);
    }
  }

  /**
   * Tests that the role is not flagged as an administrator role.
   *
   * The is_admin flag grants every permission on the site, including those of
   * modules installed later, which would bypass the enumerated set entirely.
   */
  public function testRoleIsNotAdmin(): void {
    // Assert.
    $this->assertFalse($this->loadRole()['is_admin']);
  }

  /**
   * Tests the media access the authoring contract documents.
   */
  #[DataProvider('dataProviderMediaPermission')]
  public function testMediaPermission(string $permission, bool $granted): void {
    // Prepare.
    $permissions = $this->loadRole()['permissions'];

    // Assert.
    $granted
      ? $this->assertContains($permission, $permissions)
      : $this->assertNotContains($permission, $permissions);
  }

  /**
   * Data provider for testMediaPermission.
   */
  public static function dataProviderMediaPermission(): \Iterator {
    yield 'create images' => ['create civictheme_image media', TRUE];
    yield 'create media' => ['create media', TRUE];
    yield 'read own unpublished media' => ['view own unpublished media', TRUE];
    yield 'update any media' => ['update any media', FALSE];
    yield 'delete any media' => ['delete any media', FALSE];
  }

  /**
   * Reads the exported service role configuration.
   */
  protected function loadRole(): array {
    return $this->loadConfig('user.role.do_content_api.yml');
  }

  /**
   * Reads an exported configuration file from the default config directory.
   */
  protected function loadConfig(string $file_name): array {
    $path = dirname($this->root) . '/config/default/' . $file_name;
    $this->assertFileExists($path);

    return Yaml::parseFile($path);
  }

}
