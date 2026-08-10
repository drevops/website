<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Gives each automated list on a page a pager of its own.
 *
 * Views namespaces the page a pager is on by its element id, and the automated
 * list view carries a single id for every instance placed through the
 * paragraph. Handing each instance its own id puts them in separate slots of
 * the 'page' query parameter, which is what lets one advance without moving
 * the others.
 */
final class AutomatedListPagerHook {

  /**
   * Upper bound on the slots restored from the 'page' query parameter.
   *
   * The parameter is user input and every slot restored from it widens each
   * pager URL on the page, so a hand-written one cannot be allowed to set the
   * width. No page carries anywhere near this many lists.
   */
  private const int MAX_RESTORED_PAGERS = 64;

  /**
   * Element ids handed out so far, keyed by the list they belong to.
   *
   * @var array<string, int>
   */
  protected array $allocated = [];

  /**
   * The request the ids above were handed out for.
   */
  protected ?Request $request = NULL;

  public function __construct(
    protected RequestStack $requestStack,
    protected PagerManagerInterface $pagerManager,
  ) {}

  /**
   * Implements hook_civictheme_automated_list_view_alter().
   */
  #[Hook('civictheme_automated_list_view_alter')]
  public function alter(ViewExecutable $view): void {
    $options = $view->display_handler->getOption('pager');

    if (!is_array($options) || !isset($options['options']['id'])) {
      return;
    }

    $pager = $view->display_handler->getPlugin('pager');

    // A list showing a fixed number of items has no pager to keep apart, and
    // an id spent on it would leave a dead slot in every URL on the page.
    if (!$pager instanceof PagerPluginBase || !$pager->usePager()) {
      return;
    }

    $this->restoreRequestedPages();

    $id = $this->allocate($this->listKey($view));

    // A display builds its pager plugin once and never rebuilds it from its
    // options, so the id has to reach the instance the view will run with.
    // The display's own copy is moved with it to keep the two in step.
    $pager->options['id'] = $id;
    $options['options']['id'] = $id;

    $view->display_handler->setOption('pager', $options);
  }

  /**
   * Puts the pages the request already carries back onto the pager manager.
   *
   * A pager link lists a page for every element the manager knows about at the
   * moment it is built, and each list registers its element only once its own
   * turn to run comes around. Without this, the list that runs first would
   * write links naming only its own element, and following one of them would
   * send every later list back to its first page.
   */
  protected function restoreRequestedPages(): void {
    $parameter = $this->requestStack->getCurrentRequest()?->query->all()['page'] ?? NULL;

    if (!is_string($parameter) || $parameter === '') {
      return;
    }

    foreach (array_slice(explode(',', $parameter), 0, self::MAX_RESTORED_PAGERS) as $element => $page) {
      if (!ctype_digit($page) || $this->pagerManager->getPager($element) !== NULL) {
        continue;
      }

      // A total and a limit that leave the page within range, because the
      // pager clamps a page past its last one back to the first. The list
      // owning this element overwrites the placeholder once it runs.
      $this->pagerManager->createPager((int) $page + 1, 1, $element);
    }
  }

  /**
   * Reserves the next free element id for a list.
   *
   * @param string|null $key
   *   Identifier of the list, or NULL for one that cannot be identified, which
   *   is given an id of its own rather than sharing one.
   *
   * @return int
   *   The element id.
   */
  protected function allocate(?string $key): int {
    $request = $this->requestStack->getCurrentRequest();

    // Ids only have to be unique among the lists rendered together, and the
    // service outlives the request that asked for them, so the sequence starts
    // again whenever the request does.
    if ($request !== $this->request) {
      $this->request = $request;
      $this->allocated = [];
    }

    $key ??= 'unidentified:' . count($this->allocated);
    $this->allocated[$key] ??= count($this->allocated);

    return $this->allocated[$key];
  }

  /**
   * Identifies the list a view is being built for.
   *
   * @return string|null
   *   The identifier, or NULL when the list has no saved paragraph behind it.
   */
  protected function listKey(ViewExecutable $view): ?string {
    $settings = $view->component_settings ?? NULL;
    $paragraph = is_array($settings) ? ($settings['paragraph'] ?? NULL) : NULL;

    if (!$paragraph instanceof ParagraphInterface || $paragraph->id() === NULL) {
      return NULL;
    }

    return 'paragraph:' . $paragraph->id();
  }

}
