<?php

declare(strict_types=1);

namespace Drupal\Tests\do_feed\Unit\Hook;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\do_feed\FeedUrlBuilderInterface;
use Drupal\do_feed\Hook\ViewsPreViewHook;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for ViewsPreViewHook.
 */
#[CoversClass(ViewsPreViewHook::class)]
#[Group('do_feed')]
class ViewsPreViewHookTest extends UnitTestCase {

  /**
   * Tests that hook returns early for non-matching conditions.
   */
  #[DataProvider('dataProviderEarlyReturnConditions')]
  public function testEarlyReturnConditions(string $view_id, string $display_id, bool $has_request): void {
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($has_request ? new Request() : NULL);

    $hook = new ViewsPreViewHook(
      $this->createMock(FeedUrlBuilderInterface::class),
      $this->createMock(EntityTypeManagerInterface::class),
      $request_stack,
      $this->createMock(AliasManagerInterface::class),
    );

    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn($view_id);
    $view->current_display = $display_id;
    $view->expects($this->never())->method('setTitle');

    $hook->preRender($view);
  }

  /**
   * Data provider for `testEarlyReturnConditions()`.
   *
   * @return array<string, array{string, string, bool}>
   *   Test cases: view_id, display_id, has_request.
   */
  public static function dataProviderEarlyReturnConditions(): array {
    return [
      'wrong view id' => ['other_view', 'feed_1', TRUE],
      'wrong display id' => ['feed', 'page_1', TRUE],
      'no request' => ['feed', 'feed_1', FALSE],
    ];
  }

  /**
   * Tests early return when alias does not match feed pattern.
   */
  public function testEarlyReturnWhenAliasDoesNotMatch(): void {
    $request = Request::create('/some/other/path');
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $alias_manager->method('getAliasByPath')->willReturn('/some/other/path');

    $url_builder = $this->createMock(FeedUrlBuilderInterface::class);
    $url_builder->method('getPrefix')->willReturn('feed');

    $hook = new ViewsPreViewHook(
      $url_builder,
      $this->createMock(EntityTypeManagerInterface::class),
      $request_stack,
      $alias_manager,
    );

    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('feed');
    $view->current_display = 'feed_1';
    $view->expects($this->never())->method('setTitle');

    $hook->preRender($view);
  }

  /**
   * Tests early return when no paragraph matches the slug.
   */
  public function testEarlyReturnWhenNoParagraphFound(): void {
    $request = Request::create('/feed/civictheme_page/1/all');
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $alias_manager->method('getAliasByPath')->willReturn('/feed/nonexistent');

    $url_builder = $this->createMock(FeedUrlBuilderInterface::class);
    $url_builder->method('getPrefix')->willReturn('feed');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('paragraph')->willReturn($storage);

    $hook = new ViewsPreViewHook($url_builder, $entity_type_manager, $request_stack, $alias_manager);

    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('feed');
    $view->current_display = 'feed_1';
    $view->expects($this->never())->method('setTitle');

    $hook->preRender($view);
  }

  /**
   * Tests title and description override from paragraph fields.
   */
  #[DataProvider('dataProviderTitleAndDescriptionOverride')]
  public function testTitleAndDescriptionOverride(string $title, string $description, bool $expect_title_set, bool $expect_description_set): void {
    $request = Request::create('/feed/civictheme_page/1/all');
    $request_stack = $this->createMock(RequestStack::class);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $alias_manager->method('getAliasByPath')->willReturn('/feed/blog');

    $url_builder = $this->createMock(FeedUrlBuilderInterface::class);
    $url_builder->method('getPrefix')->willReturn('feed');

    $title_field = (object) ['value' => $title ?: NULL];
    $description_field = (object) ['value' => $description ?: NULL];

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('get')->willReturnCallback(
      fn(string $name) => match ($name) {
        'field_c_p_list_feed_title' => $title_field,
        'field_c_p_list_feed_description' => $description_field,
        default => throw new \InvalidArgumentException('Unexpected field: ' . $name),
      }
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$paragraph]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('paragraph')->willReturn($storage);

    $hook = new ViewsPreViewHook($url_builder, $entity_type_manager, $request_stack, $alias_manager);

    $style_plugin = new class() extends StylePluginBase {

      /**
       * {@inheritdoc}
       */
      public function __construct() {}

    };
    $style_plugin->options = ['description' => ''];

    // Use anonymous class to avoid mock property chain issues.
    $view = new class() extends ViewExecutable {

      /**
       * The captured title value set by `setTitle()`.
       */
      public ?string $capturedTitle = NULL;

      /**
       * {@inheritdoc}
       */
      public function __construct() {}

      /**
       * {@inheritdoc}
       */
      public function id(): string {
        return 'feed';
      }

      /**
       * {@inheritdoc}
       *
       * @phpstan-ignore missingType.parameter
       */
      #[\Override]
      public function setTitle($title): bool {
        $this->capturedTitle = $title;
        return TRUE;
      }

    };
    $view->current_display = 'feed_1';
    $view->style_plugin = $style_plugin;

    $hook->preRender($view);

    if ($expect_title_set) {
      $this->assertEquals($title, $view->capturedTitle);
    }
    else {
      $this->assertNull($view->capturedTitle);
    }

    if ($expect_description_set) {
      $this->assertEquals($description, $style_plugin->options['description']);
    }
    else {
      $this->assertEquals('', $style_plugin->options['description']);
    }
  }

  /**
   * Data provider for `testTitleAndDescriptionOverride()`.
   *
   * @return array<string, array{string, string, bool, bool}>
   *   Test cases: title, description, expect_title_set, expect_description_set.
   */
  public static function dataProviderTitleAndDescriptionOverride(): array {
    return [
      'both title and description' => ['My Feed', 'Feed description', TRUE, TRUE],
      'title only' => ['My Feed', '', TRUE, FALSE],
      'description only' => ['', 'Feed description', FALSE, TRUE],
      'neither' => ['', '', FALSE, FALSE],
    ];
  }

}
