<?php

declare(strict_types=1);

namespace Drupal\do_feed;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Constructs feed URLs from paragraph field values.
 */
final readonly class FeedUrlBuilder implements FeedUrlBuilderInterface {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function buildInternalPath(ParagraphInterface $paragraph): string {
    $prefix = $this->getPrefix();

    $content_type = 'all';
    if ($paragraph->hasField('field_c_p_list_content_type') && !$paragraph->get('field_c_p_list_content_type')->isEmpty()) {
      $content_type = $paragraph->get('field_c_p_list_content_type')->value;
    }

    $topics = 'all';
    if ($paragraph->hasField('field_c_p_list_topics') && !$paragraph->get('field_c_p_list_topics')->isEmpty()) {
      $topic_ids = array_column($paragraph->get('field_c_p_list_topics')->getValue(), 'target_id');
      $topics = implode('+', $topic_ids);
    }

    $sections = 'all';
    if ($paragraph->hasField('field_c_p_list_site_sections') && !$paragraph->get('field_c_p_list_site_sections')->isEmpty()) {
      $section_ids = array_column($paragraph->get('field_c_p_list_site_sections')->getValue(), 'target_id');
      $sections = implode('+', $section_ids);
    }

    return sprintf('%s/%s/%s/%s', $prefix, $content_type, $topics, $sections);
  }

  /**
   * {@inheritdoc}
   */
  public function buildAliasPath(ParagraphInterface $paragraph): string {
    $prefix = $this->getPrefix();
    $slug = $paragraph->get('field_c_p_list_feed_slug')->value;

    return sprintf('/%s/%s', $prefix, $slug);
  }

  /**
   * Gets the configured path prefix.
   */
  public function getPrefix(): string {
    return $this->configFactory->get('do_feed.settings')->get('path_prefix') ?? 'feed';
  }

}
