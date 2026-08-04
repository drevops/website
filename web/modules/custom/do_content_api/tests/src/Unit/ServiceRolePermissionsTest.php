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
  public static function dataProviderManagementPermission(): array {
    return [
      'administer content' => ['administer nodes'],
      'content overview' => ['access content overview'],
      'read unpublished content' => ['view any unpublished content'],
      'read pending revisions' => ['view latest version'],
      'authoring api gate' => ['use content authoring api'],
      'key authentication' => ['use key authentication'],
    ];
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
      if (preg_match('/^create (\w+) content$/', $permission, $matches)) {
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
  public static function dataProviderMediaPermission(): array {
    return [
      'create images' => ['create civictheme_image media', TRUE],
      'create media' => ['create media', TRUE],
      'read own unpublished media' => ['view own unpublished media', TRUE],
      'update any media' => ['update any media', FALSE],
      'delete any media' => ['delete any media', FALSE],
    ];
  }

  /**
   * Tests that the role holds no permission beyond content management.
   */
  #[DataProvider('dataProviderWithheldPermission')]
  public function testWithheldPermission(string $permission): void {
    // Assert.
    $this->assertNotContains($permission, $this->loadRole()['permissions']);
  }

  /**
   * Data provider for testWithheldPermission.
   */
  public static function dataProviderWithheldPermission(): array {
    return [
      'node access bypass' => ['bypass node access'],
      'user administration' => ['administer users'],
      'permission administration' => ['administer permissions'],
      'site configuration' => ['administer site configuration'],
      'module administration' => ['administer modules'],
    ];
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
