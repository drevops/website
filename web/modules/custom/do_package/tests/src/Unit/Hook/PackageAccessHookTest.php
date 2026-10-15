<?php

declare(strict_types=1);

namespace Drupal\Tests\do_package\Unit\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\do_package\Hook\PackageAccessHook;
use Drupal\media\MediaInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for PackageAccessHook.
 */
#[CoversClass(PackageAccessHook::class)]
#[Group('do_package')]
class PackageAccessHookTest extends UnitTestCase {

  /**
   * Tests access decisions on existing media.
   */
  #[DataProvider('dataProviderMediaAccess')]
  public function testMediaAccess(string $bundle, string $operation, bool $has_permission, string $expected_state): void {
    // Prepare.
    $media = $this->createMock(MediaInterface::class);
    $media->method('bundle')->willReturn($bundle);
    $hook = new PackageAccessHook($this->createAccount(FALSE));

    // Act.
    $result = $hook->mediaAccess($media, $operation, $this->createAccount($has_permission));

    // Assert.
    $this->assertAccessState($expected_state, $result);
    $this->assertInstanceOf(CacheableDependencyInterface::class, $result);
    $this->assertSame($bundle === PackageAccessHook::BUNDLE ? ['user.permissions'] : [], $result->getCacheContexts());
  }

  /**
   * Data provider for testMediaAccess().
   */
  public static function dataProviderMediaAccess(): \Iterator {
    yield 'package view without permission' => ['package', 'view', FALSE, 'forbidden'];
    yield 'package update without permission' => ['package', 'update', FALSE, 'forbidden'];
    yield 'package delete without permission' => ['package', 'delete', FALSE, 'forbidden'];
    yield 'package revision history without permission' => ['package', 'view all revisions', FALSE, 'forbidden'];
    yield 'package view with permission' => ['package', 'view', TRUE, 'neutral'];
    yield 'package update with permission' => ['package', 'update', TRUE, 'neutral'];
    yield 'package delete with permission' => ['package', 'delete', TRUE, 'neutral'];
    yield 'other bundle without permission' => ['civictheme_document', 'view', FALSE, 'neutral'];
    yield 'other bundle update without permission' => ['civictheme_document', 'update', FALSE, 'neutral'];
  }

  /**
   * Tests create access decisions.
   */
  #[DataProvider('dataProviderMediaCreateAccess')]
  public function testMediaCreateAccess(?string $bundle, bool $has_permission, string $expected_state): void {
    // Prepare.
    $hook = new PackageAccessHook($this->createAccount(FALSE));

    // Act.
    $result = $hook->mediaCreateAccess($this->createAccount($has_permission), ['entity_type_id' => 'media'], $bundle);

    // Assert.
    $this->assertAccessState($expected_state, $result);
  }

  /**
   * Data provider for testMediaCreateAccess().
   */
  public static function dataProviderMediaCreateAccess(): \Iterator {
    yield 'package without permission' => ['package', FALSE, 'forbidden'];
    yield 'package with permission' => ['package', TRUE, 'neutral'];
    yield 'other bundle without permission' => ['civictheme_image', FALSE, 'neutral'];
    yield 'no bundle without permission' => [NULL, FALSE, 'neutral'];
  }

  /**
   * Tests that access-checked media queries exclude packages.
   */
  #[DataProvider('dataProviderQueryMediaAccessAlter')]
  public function testQueryMediaAccessAlter(array $tables, ?bool $metadata_permission, bool $current_user_permission, ?string $expected_alias): void {
    // Prepare.
    $query = $this->createMock(SelectInterface::class);
    $query->method('getMetaData')->with('account')->willReturn($metadata_permission === NULL ? NULL : $this->createAccount($metadata_permission));
    $query->method('getTables')->willReturn($tables);

    if ($expected_alias === NULL) {
      $query->expects($this->never())->method('condition');
    }
    else {
      $query->expects($this->once())->method('condition')->with($expected_alias . '.bundle', PackageAccessHook::BUNDLE, '<>');
    }

    $hook = new PackageAccessHook($this->createAccount($current_user_permission));

    // Act.
    $hook->queryMediaAccessAlter($query);
  }

  /**
   * Data provider for testQueryMediaAccessAlter().
   */
  public static function dataProviderQueryMediaAccessAlter(): \Iterator {
    $entity_query = ['base_table' => ['table' => 'media', 'join type' => NULL]];
    $views_query = ['media_field_data' => ['table' => 'media_field_data', 'join type' => NULL]];

    yield 'entity query without permission' => [$entity_query, NULL, FALSE, 'base_table'];
    yield 'views query without permission' => [$views_query, NULL, FALSE, 'media_field_data'];
    yield 'entity query with permission' => [$entity_query, NULL, TRUE, NULL];
    yield 'query account with permission' => [$entity_query, TRUE, FALSE, NULL];
    yield 'query account without permission' => [$entity_query, FALSE, TRUE, 'base_table'];
    yield 'revision base table' => [['base_table' => ['table' => 'media_revision', 'join type' => NULL]], NULL, FALSE, NULL];
    yield 'media joined to another base table' => [
      [
        'node_field_data' => ['table' => 'node_field_data', 'join type' => NULL],
        'media_field_data' => ['table' => 'media_field_data', 'join type' => 'LEFT'],
      ],
      NULL,
      FALSE,
      NULL,
    ];
    yield 'no tables' => [[], NULL, FALSE, NULL];
  }

  /**
   * Tests that a query other than a select is left untouched.
   */
  public function testQueryMediaAccessAlterIgnoresNonSelect(): void {
    // Prepare.
    $query = $this->createMock(AlterableInterface::class);
    $query->expects($this->never())->method('getMetaData');
    $hook = new PackageAccessHook($this->createAccount(FALSE));

    // Act.
    $hook->queryMediaAccessAlter($query);
  }

  /**
   * Creates an account holding or lacking the package access permission.
   */
  protected function createAccount(bool $has_permission): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->with(PackageAccessHook::PERMISSION)->willReturn($has_permission);

    return $account;
  }

  /**
   * Asserts that an access result is in the expected state.
   */
  protected function assertAccessState(string $expected_state, AccessResultInterface $result): void {
    $this->assertSame($expected_state === 'allowed', $result->isAllowed());
    $this->assertSame($expected_state === 'forbidden', $result->isForbidden());
    $this->assertSame($expected_state === 'neutral', $result->isNeutral());
  }

}
