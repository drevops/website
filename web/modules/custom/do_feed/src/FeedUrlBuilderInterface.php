<?php

declare(strict_types=1);

namespace Drupal\do_feed;

use Drupal\paragraphs\ParagraphInterface;

/**
 * Constructs feed URLs from paragraph field values.
 */
interface FeedUrlBuilderInterface {

  /**
   * Builds the internal feed path from paragraph field values.
   *
   * @return string
   *   Internal path like "feed/civictheme_page/1+2/all".
   */
  public function buildInternalPath(ParagraphInterface $paragraph): string;

  /**
   * Builds the alias path from the paragraph's slug field.
   *
   * @return string
   *   Alias path like "/feed/blog".
   */
  public function buildAliasPath(ParagraphInterface $paragraph): string;

  /**
   * Gets the configured path prefix.
   */
  public function getPrefix(): string;

}
