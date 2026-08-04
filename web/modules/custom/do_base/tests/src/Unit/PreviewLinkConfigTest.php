<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ExportedConfigTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the exported configuration behind shareable preview links.
 *
 * A preview link exposes unpublished content on a public URL, so the settings
 * that bound it and the roles allowed to mint one are pinned here. Every one of
 * these values is reachable from the admin UI and lands back in the repository
 * through a routine re-export, where a widened lifetime or a dropped permission
 * reads as noise in a large diff.
 */
#[CoversNothing]
#[Group('do_base')]
class PreviewLinkConfigTest extends UnitTestCase {

  use ExportedConfigTrait;

  /**
   * Tests that the module providing preview links stays installed.
   */
  public function testModuleIsInstalled(): void {
    // Act.
    $modules = $this->loadConfig('core.extension.yml')['module'];

    // Assert.
    $this->assertArrayHasKey('preview_link', $modules);
  }

  /**
   * Tests that a preview link's bounds are exported as intended.
   */
  #[DataProvider('dataProviderSetting')]
  public function testSetting(string $key, mixed $expected): void {
    // Act.
    $settings = $this->loadConfig('preview_link.settings.yml');

    // Assert.
    $this->assertSame($expected, $settings[$key]);
  }

  /**
   * Data provider for testSetting.
   */
  public static function dataProviderSetting(): \Iterator {
    yield 'links expire after a week' => ['expiry_seconds', 604800];
    // A page is assembled from paragraphs and media, which have to travel with
    // the node for the preview to render the way the published page will.
    yield 'a link can carry referenced entities' => ['multiple_entities', TRUE];
    yield 'the editor is told a link was created' => ['display_message', 'subsequent'];
  }

  /**
   * Tests that every content type can be previewed.
   *
   * An empty bundle list means the entity type is enabled for all of its
   * bundles, which is what keeps a content type added later from silently
   * shipping without preview links.
   */
  public function testEveryContentTypeIsPreviewable(): void {
    // Prepare.
    $enabled = $this->loadConfig('preview_link.settings.yml')['enabled_entity_types'];
    $this->assertArrayHasKey('node', $enabled, 'Preview links are not enabled for any content type.');

    if ($enabled['node'] === []) {
      return;
    }

    // Act.
    $missing = array_diff($this->loadBundleNames('node.type'), $enabled['node']);

    // Assert.
    $this->assertSame([], array_values($missing), 'Content types are missing from the preview link settings.');
  }

  /**
   * Tests that the editorial roles can mint a preview link.
   */
  #[DataProvider('dataProviderRoleCanGenerate')]
  public function testRoleCanGenerate(string $role_id): void {
    // Act.
    $permissions = $this->loadConfig('user.role.' . $role_id . '.yml')['permissions'];

    // Assert.
    $this->assertContains('generate preview links', $permissions);
  }

  /**
   * Data provider for testRoleCanGenerate.
   */
  public static function dataProviderRoleCanGenerate(): \Iterator {
    yield 'content author' => ['civictheme_content_author'];
    yield 'content approver' => ['civictheme_content_approver'];
    yield 'site administrator' => ['civictheme_site_administrator'];
  }

  /**
   * Tests that site administrators can change the preview link bounds.
   */
  public function testSiteAdministratorCanAdminister(): void {
    // Act.
    $permissions = $this->loadConfig('user.role.civictheme_site_administrator.yml')['permissions'];

    // Assert.
    $this->assertContains('administer preview link settings', $permissions);
  }

  /**
   * Tests that no other role can change the preview link bounds.
   */
  #[DataProvider('dataProviderRoleCannotAdminister')]
  public function testRoleCannotAdminister(string $role_id): void {
    // Act.
    $permissions = $this->loadConfig('user.role.' . $role_id . '.yml')['permissions'];

    // Assert.
    $this->assertNotContains('administer preview link settings', $permissions);
  }

  /**
   * Data provider for testRoleCannotAdminister.
   */
  public static function dataProviderRoleCannotAdminister(): \Iterator {
    yield 'content author' => ['civictheme_content_author'];
    yield 'content approver' => ['civictheme_content_approver'];
    yield 'anonymous' => ['anonymous'];
    yield 'authenticated' => ['authenticated'];
  }

  /**
   * Tests that a preview link needs no permission to open.
   *
   * The token is the only credential a recipient has, so granting either
   * permission to a site-wide role would hand it to every visitor instead.
   */
  #[DataProvider('dataProviderUnprivilegedRoleCannotGenerate')]
  public function testUnprivilegedRoleCannotGenerate(string $role_id): void {
    // Act.
    $permissions = $this->loadConfig('user.role.' . $role_id . '.yml')['permissions'];

    // Assert.
    $this->assertNotContains('generate preview links', $permissions);
  }

  /**
   * Data provider for testUnprivilegedRoleCannotGenerate.
   */
  public static function dataProviderUnprivilegedRoleCannotGenerate(): \Iterator {
    yield 'anonymous' => ['anonymous'];
    yield 'authenticated' => ['authenticated'];
  }

}
