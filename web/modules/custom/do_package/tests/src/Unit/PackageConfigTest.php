<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Unit;

use Drupal\do_package\Hook\PackageAccessHook;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\do_base\Traits\ExportedConfigTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the exported configuration behind private package media.
 *
 * Every value pinned here is editable in the admin UI and returns to the
 * repository through a routine re-export, where a switch to the public scheme
 * or a widened role reads as noise in a large diff.
 */
#[CoversNothing]
#[Group('do_package')]
class PackageConfigTest extends UnitTestCase {

  use ExportedConfigTrait;

  /**
   * The field holding the package file.
   */
  protected const string SOURCE_FIELD = 'field_do_m_package';

  /**
   * Tests that the module gating packages stays installed.
   */
  public function testModuleIsInstalled(): void {
    // Act.
    $modules = $this->loadConfig('core.extension.yml')['module'];

    // Assert.
    $this->assertArrayHasKey('do_package', $modules);
  }

  /**
   * Tests the media type definition.
   *
   * The enforced dependency removes the type together with the module, so
   * packages cannot outlive the hook that gates them.
   */
  public function testMediaType(): void {
    // Act.
    $type = $this->loadConfig('media.type.' . PackageAccessHook::BUNDLE . '.yml');

    // Assert.
    $this->assertSame('file', $type['source']);
    $this->assertSame(self::SOURCE_FIELD, $type['source_configuration']['source_field']);
    $this->assertContains('do_package', $type['dependencies']['enforced']['module'] ?? []);
  }

  /**
   * Tests that package files are stored on the private file system.
   */
  public function testFileStorageIsPrivate(): void {
    // Act.
    $storage = $this->loadConfig('field.storage.media.' . self::SOURCE_FIELD . '.yml');

    // Assert.
    $this->assertSame('file', $storage['type']);
    $this->assertSame('private', $storage['settings']['uri_scheme']);
  }

  /**
   * Tests that package files are uploaded into the package directory.
   *
   * File listings hide this directory from accounts without package access.
   */
  public function testFileDirectory(): void {
    // Act.
    $storage = $this->loadConfig('field.storage.media.' . self::SOURCE_FIELD . '.yml');
    $field = $this->loadConfig('field.field.media.' . PackageAccessHook::BUNDLE . '.' . self::SOURCE_FIELD . '.yml');

    // Assert.
    $this->assertStringStartsWith(PackageAccessHook::DIRECTORY, $storage['settings']['uri_scheme'] . '://' . $field['settings']['file_directory'] . '/');
  }

  /**
   * Tests that a package requires a zip or 7z file.
   */
  public function testFileField(): void {
    // Act.
    $field = $this->loadConfig('field.field.media.' . PackageAccessHook::BUNDLE . '.' . self::SOURCE_FIELD . '.yml');

    // Assert.
    $this->assertTrue($field['required']);
    $this->assertSame('zip 7z', $field['settings']['file_extensions']);
  }

  /**
   * Tests that packages stay out of the editorial workflow.
   *
   * A moderated package would start as a draft, which the content API service
   * cannot view until someone publishes it.
   */
  public function testPackageIsNotModerated(): void {
    // Act.
    $workflow = $this->loadConfig('workflows.workflow.civictheme_editorial.yml');

    // Assert.
    $this->assertNotContains(PackageAccessHook::BUNDLE, $workflow['type_settings']['entity_types']['media'] ?? []);
  }

  /**
   * Tests that no role beyond the expected ones holds each permission.
   */
  #[DataProvider('dataProviderPermissionIsConfinedToItsRoles')]
  public function testPermissionIsConfinedToItsRoles(string $permission, array $expected): void {
    // Act.
    $granted = $this->loadRolesWithPermission($permission);

    // Assert.
    $this->assertSame($expected, $granted);
  }

  /**
   * Data provider for testPermissionIsConfinedToItsRoles().
   */
  public static function dataProviderPermissionIsConfinedToItsRoles(): \Iterator {
    yield 'access packages' => [PackageAccessHook::PERMISSION, ['administrator', 'civictheme_site_administrator', 'do_content_api']];
    yield 'create packages' => ['create package media', ['administrator', 'civictheme_site_administrator']];
    yield 'edit any package' => ['edit any package media', ['administrator', 'civictheme_site_administrator']];
    yield 'delete any package' => ['delete any package media', ['administrator', 'civictheme_site_administrator']];
  }

}
