<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit\Hook;

use Drupal\Core\Session\AccountInterface;
use Drupal\do_content_api\Hook\EntityCreateAccessHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for EntityCreateAccessHook.
 */
#[CoversClass(EntityCreateAccessHook::class)]
#[Group('do_content_api')]
class EntityCreateAccessHookTest extends UnitTestCase {

  /**
   * Tests paragraph create-access decisions.
   */
  #[DataProvider('dataProviderEntityCreateAccess')]
  public function testEntityCreateAccess(array $context, bool $has_permission, ?string $bundle, bool $expected_allowed): void {
    // Prepare.
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn($has_permission);
    $hook = new EntityCreateAccessHook();

    // Act.
    $result = $hook->entityCreateAccess($account, $context, $bundle);

    // Assert.
    $this->assertSame($expected_allowed, $result->isAllowed());
  }

  /**
   * Data provider for testEntityCreateAccess().
   */
  public static function dataProviderEntityCreateAccess(): array {
    return [
      'non-paragraph entity type is ignored' => [['entity_type_id' => 'node'], TRUE, 'civictheme_content', FALSE],
      'missing entity type id is ignored' => [[], TRUE, 'civictheme_content', FALSE],
      'permitted user, allowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_content', TRUE],
      'permitted user, nested allowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_accordion_panel', TRUE],
      'permitted user, disallowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_event_card_ref', FALSE],
      'unpermitted user, allowed bundle' => [['entity_type_id' => 'paragraph'], FALSE, 'civictheme_content', FALSE],
      'permitted user, null bundle' => [['entity_type_id' => 'paragraph'], TRUE, NULL, FALSE],
    ];
  }

}
