<?php

declare(strict_types=1);

namespace Drupal\Tests\do_content_api\Unit\Hook;

use Drupal\Core\Session\AccountInterface;
use Drupal\do_content_api\Hook\EntityCreateAccessHook;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
  public function testEntityCreateAccess(array $context, bool $has_permission, ?string $bundle, ?string $request_format, string $expected_state): void {
    // Prepare.
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->with('use content authoring api')->willReturn($has_permission);
    $request_stack = new RequestStack();

    if ($request_format !== NULL) {
      $request = Request::create('/');
      $request->setRequestFormat($request_format);
      $request_stack->push($request);
    }

    $hook = new EntityCreateAccessHook($request_stack);

    // Act.
    $result = $hook->entityCreateAccess($account, $context, $bundle);

    // Assert.
    $this->assertSame($expected_state === 'allowed', $result->isAllowed());
    $this->assertSame($expected_state === 'forbidden', $result->isForbidden());
    $this->assertSame($expected_state === 'neutral', $result->isNeutral());
  }

  /**
   * Data provider for testEntityCreateAccess().
   */
  public static function dataProviderEntityCreateAccess(): \Iterator {
    yield 'non-paragraph entity type is ignored' => [['entity_type_id' => 'node'], TRUE, 'civictheme_content', 'api_json', 'neutral'];
    yield 'missing entity type id is ignored' => [[], TRUE, 'civictheme_content', 'api_json', 'neutral'];
    yield 'permitted user, allowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_content', 'api_json', 'allowed'];
    yield 'permitted user, nested allowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_accordion_panel', 'api_json', 'allowed'];
    yield 'permitted user, disallowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_event_card_ref', 'api_json', 'forbidden'];
    yield 'unpermitted user, allowed bundle' => [['entity_type_id' => 'paragraph'], FALSE, 'civictheme_content', 'api_json', 'neutral'];
    yield 'permitted user, null bundle' => [['entity_type_id' => 'paragraph'], TRUE, NULL, 'api_json', 'neutral'];
    yield 'html request, disallowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_event_card_ref', 'html', 'neutral'];
    yield 'html request, allowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_content', 'html', 'neutral'];
    yield 'no request, disallowed bundle' => [['entity_type_id' => 'paragraph'], TRUE, 'civictheme_event_card_ref', NULL, 'forbidden'];
  }

}
