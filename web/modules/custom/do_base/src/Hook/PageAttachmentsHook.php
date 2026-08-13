<?php

declare(strict_types=1);

namespace Drupal\do_base\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\csp\Csp;
use Drupal\do_base\NavigationScriptHash;
use Drupal\file\FileInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\media\MediaInterface;

/**
 * Page attachment hooks for do_base module.
 */
final class PageAttachmentsHook {

  /**
   * Image style the banner paints its background with.
   */
  private const string IMAGE_STYLE = 'banner_background';

  /**
   * Routes that render a node's banner.
   */
  private const array BANNER_ROUTES = [
    'entity.node.canonical',
    'entity.node.revision',
    'entity.node.latest_version',
  ];

  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected NavigationScriptHash $navigationScriptHash,
  ) {
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function attach(array &$attachments): void {
    $this->attachPreviewLinkRobots($attachments);
    $this->attachBannerPreload($attachments);

    if ($this->moduleHandler->moduleExists('csp')) {
      $this->attachCspNonce($attachments);
      $this->attachCspScriptHashes($attachments);
    }
  }

  /**
   * Preloads the banner background image.
   *
   * The banner paints its background from CSS, so the browser cannot discover
   * the file until the stylesheet has been fetched and parsed.
   */
  protected function attachBannerPreload(array &$attachments): void {
    if (!$this->routeRendersBanner()) {
      return;
    }

    $url = $this->bannerBackgroundUrl();

    if ($url === NULL) {
      return;
    }

    $attachments['#attached']['html_head_link'][] = [
      [
        'rel' => 'preload',
        'href' => $url,
        'as' => 'image',
        'fetchpriority' => 'high',
      ],
    ];
  }

  /**
   * Resolves the styled URL of the current node's banner background.
   *
   * @return string|null
   *   The URL, or NULL when there is no node, no background, or the file cannot
   *   be processed into a derivative.
   */
  protected function bannerBackgroundUrl(): ?string {
    $node = $this->routeMatch->getParameter('node_revision') ?: $this->routeMatch->getParameter('node');

    if (!$node instanceof FieldableEntityInterface || !$node->hasField('field_c_n_banner_background')) {
      return NULL;
    }

    $media = $node->get('field_c_n_banner_background')->entity;
    if (!$media instanceof MediaInterface) {
      return NULL;
    }

    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? NULL;
    if (empty($source_field) || !$media->hasField($source_field)) {
      return NULL;
    }

    $file = $media->get($source_field)->entity;
    if (!$file instanceof FileInterface) {
      return NULL;
    }

    $style = $this->entityTypeManager->getStorage('image_style')->load(self::IMAGE_STYLE);
    // A vector has no derivative, so the stylesheet requests the original and a
    // preload of anything else would fetch the file twice.
    if (!$style instanceof ImageStyleInterface || !$style->supportsUri($file->getFileUri())) {
      return NULL;
    }

    return $style->buildUrl($file->getFileUri());
  }

  /**
   * Checks whether the current route renders a node's banner.
   *
   * Every other route carrying a node parameter - the forms, the delete
   * confirmation, the revision list - resolves the same background without ever
   * drawing it.
   *
   * @return bool
   *   TRUE when the route renders the node page itself.
   */
  protected function routeRendersBanner(): bool {
    if (in_array($this->routeMatch->getRouteName(), self::BANNER_ROUTES, TRUE)) {
      return TRUE;
    }

    $route = $this->routeMatch->getRouteObject();

    // A preview link route is named after the entity type it was built for, so
    // the option it carries is the only stable way to recognise one.
    return $route !== NULL && $route->getOption('_preview_link_route') === TRUE;
  }

  /**
   * Keeps preview link pages out of search indexes.
   */
  protected function attachPreviewLinkRobots(array &$attachments): void {
    $route = $this->routeMatch->getRouteObject();

    if ($route === NULL || $route->getOption('_preview_link_route') !== TRUE) {
      return;
    }

    // A preview link renders unpublished content to anyone holding the token,
    // and the URL is meant to be pasted into mail and chat clients that follow
    // links, so the page must never reach a search index.
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex, nofollow',
        ],
      ],
      'do_base_preview_link_robots',
    ];
  }

  /**
   * Attaches a CSP nonce so core's inline scripts survive a strict policy.
   */
  protected function attachCspNonce(array &$attachments): void {
    // The 'unsafe-inline' fallback is only used by browsers without CSP3 nonce
    // support; modern browsers ignore it once a nonce is present.
    $existing = $attachments['#attached']['csp_nonce']['script'] ?? [];
    $attachments['#attached']['csp_nonce']['script'] = array_values(array_unique(array_merge($existing, [Csp::POLICY_UNSAFE_INLINE])));

    $libraries = $attachments['#attached']['library'] ?? [];
    if (!in_array('csp/nonce', $libraries, TRUE)) {
      $attachments['#attached']['library'][] = 'csp/nonce';
    }
  }

  /**
   * Attaches hashes for the inline scripts that carry no nonce.
   */
  protected function attachCspScriptHashes(array &$attachments): void {
    foreach ($this->navigationScriptHash->getHashes() as $hash) {
      $attachments['#attached']['csp_hash']['script-src-elem'][$hash] = [Csp::POLICY_UNSAFE_INLINE];
    }
  }

}
