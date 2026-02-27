<?php

declare(strict_types=1);

namespace Drupal\do_feed\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\do_feed\FeedUrlBuilderInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides feed view title and description from paragraph fields.
 */
final readonly class ViewsPreViewHook {

  public function __construct(
    protected FeedUrlBuilderInterface $feedUrlBuilder,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected AliasManagerInterface $aliasManager,
  ) {}

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function preRender(ViewExecutable $view): void {
    if ($view->id() !== 'feed' || $view->current_display !== 'feed_1') {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();

    if (!$request instanceof Request) {
      return;
    }

    // The request path info contains the internal path after alias
    // resolution (e.g., /feed/civictheme_page/1/all). Convert it back to the
    // alias (e.g., /feed/blog) to extract the slug.
    $internal_path = $request->getPathInfo();
    $alias = $this->aliasManager->getAliasByPath($internal_path);

    $prefix = $this->feedUrlBuilder->getPrefix();
    $pattern = '#^/' . preg_quote($prefix, '#') . '/([^/]+)$#';

    if (!preg_match($pattern, $alias, $matches)) {
      return;
    }

    $slug = $matches[1];

    $paragraphs = $this->entityTypeManager->getStorage('paragraph')->loadByProperties([
      'type' => 'civictheme_automated_list',
      'field_c_p_list_feed_slug' => $slug,
    ]);

    if (empty($paragraphs)) {
      return;
    }

    $paragraph = reset($paragraphs);

    if (!$paragraph instanceof ParagraphInterface) {
      return;
    }

    $title = $paragraph->get('field_c_p_list_feed_title')->value ?? '';
    if (!empty($title)) {
      $view->setTitle($title);
    }

    $description = $paragraph->get('field_c_p_list_feed_description')->value ?? '';
    if (!empty($description)) {
      $view->style_plugin->options['description'] = $description;
    }
  }

}
