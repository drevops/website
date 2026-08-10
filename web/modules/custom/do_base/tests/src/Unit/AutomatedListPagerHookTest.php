<?php

declare(strict_types=1);

namespace Drupal\Tests\do_base\Unit;

use Drupal\Core\Pager\Pager;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\do_base\Hook\AutomatedListPagerHook;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\ViewsPluginManager;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the pager element id handed to each automated list.
 *
 * The hook is driven directly so that each branch is reachable on its own.
 * That two lists on a rendered page then paginate apart is asserted in the
 * Behat feature.
 */
#[Group('do_base')]
class AutomatedListPagerHookTest extends DoBaseUnitTestBase {

  /**
   * Pager options as the automated list view exports them.
   */
  protected const array PAGER = [
    'type' => 'full',
    'options' => ['items_per_page' => 12, 'id' => 0],
  ];

  /**
   * Element ids written by the hook, in the order the lists were altered.
   *
   * @var int[]
   */
  protected array $assigned = [];

  /**
   * Pagers the hook restored, as element id => current page.
   *
   * @var array<int, int>
   */
  protected array $restored = [];

  /**
   * Tests that each list is given the next free element id.
   */
  public function testAssignsAnElementIdPerList(): void {
    // Prepare.
    $hook = $this->hook();

    // Act.
    $hook->alter($this->view('906'));
    $hook->alter($this->view('908'));
    $hook->alter($this->view('910'));

    // Assert.
    $this->assertSame([0, 1, 2], $this->assigned);
  }

  /**
   * Tests that a list altered twice keeps the id it was given.
   */
  public function testListKeepsItsElementId(): void {
    // Prepare.
    $hook = $this->hook();

    // Act.
    $hook->alter($this->view('906'));
    $hook->alter($this->view('908'));
    $hook->alter($this->view('906'));

    // Assert.
    $this->assertSame([0, 1, 0], $this->assigned);
  }

  /**
   * Tests that lists without a saved paragraph do not share an element id.
   */
  public function testUnsavedListsDoNotShareAnElementId(): void {
    // Prepare.
    $hook = $this->hook();

    // Act.
    $hook->alter($this->view(NULL));
    $hook->alter($this->view(NULL));

    // Assert.
    $this->assertSame([0, 1], $this->assigned);
  }

  /**
   * Tests that the sequence starts again on the next request.
   */
  public function testSequenceRestartsOnTheNextRequest(): void {
    // Prepare.
    $requests = new RequestStack();
    $requests->push(Request::create('/page'));
    $hook = $this->hook(requests: $requests);

    // Act.
    $hook->alter($this->view('906'));
    $requests->push(Request::create('/page'));
    $hook->alter($this->view('908'));

    // Assert.
    $this->assertSame([0, 0], $this->assigned);
  }

  /**
   * Tests that a list showing a fixed number of items is left alone.
   */
  public function testFixedLengthListIsLeftAlone(): void {
    // Prepare.
    $hook = $this->hook(paginates: FALSE);

    // Act.
    $hook->alter($this->view('906', ['type' => 'some', 'options' => ['items_per_page' => 3, 'id' => 0]]));

    // Assert.
    $this->assertSame([], $this->assigned);
  }

  /**
   * Tests that a pager option the hook cannot read is left alone.
   *
   * @param mixed $pager
   *   The pager option as the display reports it.
   */
  #[DataProvider('dataProviderUnreadablePagerIsLeftAlone')]
  public function testUnreadablePagerIsLeftAlone(mixed $pager): void {
    // Prepare.
    $hook = $this->hook();

    // Act.
    $hook->alter($this->view('906', $pager));

    // Assert.
    $this->assertSame([], $this->assigned);
  }

  /**
   * Data provider for testUnreadablePagerIsLeftAlone.
   */
  public static function dataProviderUnreadablePagerIsLeftAlone(): \Iterator {
    yield 'no pager' => [NULL];
    yield 'not an array' => ['full'];
    yield 'no type' => [['options' => ['id' => 0]]];
    yield 'no element id' => [['type' => 'full', 'options' => ['items_per_page' => 12]]];
    yield 'unknown type' => [['type' => 'nonexistent', 'options' => ['id' => 0]]];
  }

  /**
   * Tests that the pages already in the URL are put back on the manager.
   */
  public function testPagesInTheUrlAreRestored(): void {
    // Prepare.
    $hook = $this->hook(requests: $this->requests('/page?page=0,3,12'));

    // Act.
    $hook->alter($this->view('906'));

    // Assert.
    $this->assertSame([0 => 0, 1 => 3, 2 => 12], $this->restored);
  }

  /**
   * Tests that an element already carrying a pager is not overwritten.
   */
  public function testElementAlreadyPagedIsNotRestored(): void {
    // Prepare.
    $hook = $this->hook(requests: $this->requests('/page?page=0,3'), existing: [0 => new Pager(40, 6, 1)]);

    // Act.
    $hook->alter($this->view('906'));

    // Assert.
    $this->assertSame([1 => 3], $this->restored);
  }

  /**
   * Tests that a hand-written 'page' parameter cannot widen every pager URL.
   */
  public function testRestoredPagesAreCapped(): void {
    // Prepare.
    $hook = $this->hook(requests: $this->requests('/page?page=' . implode(',', array_fill(0, 200, '1'))));

    // Act.
    $hook->alter($this->view('906'));

    // Assert.
    $this->assertCount(64, $this->restored);
  }

  /**
   * Tests that a 'page' parameter no pager could have written is ignored.
   *
   * @param string $uri
   *   The request URI.
   */
  #[DataProvider('dataProviderUnusablePageParameterIsIgnored')]
  public function testUnusablePageParameterIsIgnored(string $uri): void {
    // Prepare.
    $hook = $this->hook(requests: $this->requests($uri));

    // Act.
    $hook->alter($this->view('906'));

    // Assert.
    $this->assertSame([], $this->restored);
    $this->assertSame([0], $this->assigned);
  }

  /**
   * Data provider for testUnusablePageParameterIsIgnored.
   */
  public static function dataProviderUnusablePageParameterIsIgnored(): \Iterator {
    yield 'absent' => ['/page'];
    yield 'empty' => ['/page?page='];
    yield 'an array' => ['/page?page[]=1'];
    yield 'not a number' => ['/page?page=first,last'];
    yield 'negative' => ['/page?page=-1'];
  }

  /**
   * Builds the hook under test.
   *
   * @param bool $paginates
   *   Whether the pager plugin the manager hands back splits results across
   *   pages.
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requests
   *   The request stack, defaulting to one holding a plain request.
   * @param array<int, \Drupal\Core\Pager\Pager> $existing
   *   Pagers already registered for the request, keyed by element id.
   */
  protected function hook(bool $paginates = TRUE, ?RequestStack $requests = NULL, array $existing = []): AutomatedListPagerHook {
    $plugin = $this->createMock(PagerPluginBase::class);
    $plugin->method('usePager')->willReturn($paginates);

    $plugins = $this->createMock(ViewsPluginManager::class);
    $plugins->method('hasDefinition')->willReturnCallback(static fn(string $type): bool => in_array($type, ['full', 'mini', 'some'], TRUE));
    $plugins->method('createInstance')->willReturn($plugin);

    $pagers = $this->createMock(PagerManagerInterface::class);
    $pagers->method('getPager')->willReturnCallback(static fn(int $element): ?Pager => $existing[$element] ?? NULL);
    $pagers->method('createPager')->willReturnCallback(function (int $total, int $limit, int $element): Pager {
      $pager = new Pager($total, $limit, $total - 1);
      $this->restored[$element] = $pager->getCurrentPage();

      return $pager;
    });

    return new AutomatedListPagerHook($requests ?? $this->requests('/page'), $plugins, $pagers);
  }

  /**
   * Builds a request stack holding a request for the given URI.
   */
  protected function requests(string $uri): RequestStack {
    $stack = new RequestStack();
    $stack->push(Request::create($uri));

    return $stack;
  }

  /**
   * Builds a view for an automated list, recording the id the hook writes.
   *
   * @param string|null $paragraph_id
   *   Id of the paragraph behind the list, or NULL for one not yet saved.
   * @param mixed $pager
   *   The pager option the display reports, defaulting to the exported one.
   */
  protected function view(?string $paragraph_id, mixed $pager = self::PAGER): ViewExecutable {
    $display = $this->createMock(DisplayPluginBase::class);
    $display->method('getOption')->willReturnCallback(static fn(string $option): mixed => $option === 'pager' ? $pager : NULL);
    $display->method('setOption')->willReturnCallback(function (string $option, mixed $value): mixed {
      $this->assigned[] = $value['options']['id'];

      return $value;
    });

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('id')->willReturn($paragraph_id);

    $view = $this->createMock(ViewExecutable::class);
    $view->display_handler = $display;
    // @phpstan-ignore-next-line
    $view->component_settings = ['paragraph' => $paragraph];

    return $view;
  }

}
