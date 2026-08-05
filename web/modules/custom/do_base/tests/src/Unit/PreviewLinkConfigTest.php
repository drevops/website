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
    yield 'links expire after a fortnight' => ['expiry_seconds', 1209600];
    // A page is assembled from paragraphs and media, which have to travel with
    // the node for the preview to render the way the published page will.
    yield 'a link can carry referenced entities' => ['multiple_entities', TRUE];
    // The notice reaches the recipient only when a normal URL redirected them
    // here, so landing on the link itself opens on the content, not a banner.
    yield 'a redirected recipient is told why they can see the page' => ['display_message', 'subsequent'];
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
   * Tests that no role beyond the expected ones holds each permission.
   *
   * Naming the roles that must not hold a permission would leave a role added
   * later untested, so every exported role is weighed against the allowlist
   * instead. A recipient needs no permission at all, so a site-wide role
   * holding one of these would hand link creation to every visitor.
   *
   * A role flagged 'is_admin' holds every permission without listing any, so
   * it counts as granted here. Reading only the explicit list would let a new
   * administrative role pick both permissions up unnoticed.
   */
  #[DataProvider('dataProviderPermissionIsConfinedToItsRoles')]
  public function testPermissionIsConfinedToItsRoles(string $permission, array $expected): void {
    // Act.
    $granted = [];

    foreach ($this->loadBundleNames('user.role') as $role_id) {
      $role = $this->loadConfig('user.role.' . $role_id . '.yml');

      if (($role['is_admin'] ?? FALSE) === TRUE || in_array($permission, $role['permissions'] ?? [], TRUE)) {
        $granted[] = $role_id;
      }
    }

    sort($granted);

    // Assert.
    $this->assertSame($expected, $granted);
  }

  /**
   * Data provider for testPermissionIsConfinedToItsRoles.
   */
  public static function dataProviderPermissionIsConfinedToItsRoles(): \Iterator {
    yield 'generating links' => [
      'generate preview links',
      ['administrator', 'civictheme_content_approver', 'civictheme_content_author', 'civictheme_site_administrator'],
    ];
    yield 'changing the bounds' => [
      'administer preview link settings',
      ['administrator', 'civictheme_site_administrator'],
    ];
  }

}
