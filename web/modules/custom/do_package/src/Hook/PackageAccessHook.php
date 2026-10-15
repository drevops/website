<?php

declare(strict_types=1);

namespace Drupal\do_package\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;

/**
 * Gates package media and package files behind the package access permission.
 */
final class PackageAccessHook {

  /**
   * The media type holding private packages.
   */
  public const string BUNDLE = 'package';

  /**
   * The permission required for any operation on package media.
   */
  public const string PERMISSION = 'access package media';

  /**
   * Media tables a listing query can select from as its base table.
   */
  public const array BASE_TABLES = ['media', 'media_field_data'];

  /**
   * The directory every package file is uploaded into.
   */
  public const string DIRECTORY = 'private://packages/';

  public function __construct(
    protected AccountInterface $account,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_access() for media.
   */
  #[Hook('media_access')]
  public function mediaAccess(MediaInterface $media, string $operation, AccountInterface $account): AccessResultInterface {
    if ($media->bundle() !== self::BUNDLE) {
      return AccessResult::neutral();
    }

    return $this->gate($account);
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access() for media.
   */
  #[Hook('media_create_access')]
  public function mediaCreateAccess(AccountInterface $account, array $context, ?string $entity_bundle): AccessResultInterface {
    if ($entity_bundle !== self::BUNDLE) {
      return AccessResult::neutral();
    }

    return $this->gate($account);
  }

  /**
   * Implements hook_query_TAG_alter() for the media_access tag.
   */
  #[Hook('query_media_access_alter')]
  public function queryMediaAccessAlter(AlterableInterface $query): void {
    if (!$query instanceof SelectInterface) {
      return;
    }

    $alias = $this->restrictedBaseTableAlias($query, self::BASE_TABLES);

    if ($alias !== NULL) {
      $query->condition($alias . '.bundle', self::BUNDLE, '<>');
    }
  }

  /**
   * Implements hook_query_TAG_alter() for the file_access tag.
   */
  #[Hook('query_file_access_alter')]
  public function queryFileAccessAlter(AlterableInterface $query): void {
    if (!$query instanceof SelectInterface) {
      return;
    }

    $alias = $this->restrictedBaseTableAlias($query, ['file_managed']);

    if ($alias !== NULL) {
      $query->condition($alias . '.uri', $query->escapeLike(self::DIRECTORY) . '%', 'NOT LIKE');
    }
  }

  /**
   * Returns the base table alias of a query that must exclude packages.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The access-checked query.
   * @param string[] $tables
   *   Tables the query can select from as its base table.
   *
   * @return string|null
   *   The alias, or NULL when the account holds the package access permission
   *   or the base table is not one of $tables.
   */
  protected function restrictedBaseTableAlias(SelectInterface $query, array $tables): ?string {
    $account = $query->getMetaData('account');
    $account = $account instanceof AccountInterface ? $account : $this->account;

    if ($account->hasPermission(self::PERMISSION)) {
      return NULL;
    }

    // The first table of a select is its base table.
    $query_tables = $query->getTables();
    $alias = array_key_first($query_tables);

    if ($alias === NULL || !in_array($query_tables[$alias]['table'], $tables, TRUE)) {
      return NULL;
    }

    return (string) $alias;
  }

  /**
   * Forbids access to accounts without the package access permission.
   *
   * The result is never allowed, so core's operation checks still decide for
   * accounts that hold the permission.
   */
  protected function gate(AccountInterface $account): AccessResultInterface {
    return AccessResult::forbiddenIf(!$account->hasPermission(self::PERMISSION), sprintf("The '%s' permission is required.", self::PERMISSION))->cachePerPermissions();
  }

}
