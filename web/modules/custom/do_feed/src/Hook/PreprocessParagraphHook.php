<?php

declare(strict_types=1);

namespace Drupal\do_feed\Hook;

use Drupal\do_feed\FeedUrlBuilderInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Injects RSS feed button into automated list paragraph footer.
 */
final readonly class PreprocessParagraphHook {

  public function __construct(
    protected FeedUrlBuilderInterface $feedUrlBuilder,
  ) {}

  /**
   * Implements hook_preprocess_paragraph() for civictheme_automated_list.
   */
  public function preprocessParagraph(array &$variables): void {
    $paragraph = $variables['paragraph'] ?? NULL;

    if (!$paragraph instanceof Paragraph || $paragraph->bundle() !== 'civictheme_automated_list') {
      return;
    }

    if (!$paragraph->hasField('field_c_p_list_feed_slug') || $paragraph->get('field_c_p_list_feed_slug')->isEmpty()) {
      return;
    }

    $url = $this->feedUrlBuilder->buildAliasPath($paragraph);

    $variables['footer'] = [
      '#type' => 'inline_template',
      '#template' => "{% include 'civictheme:button' with { kind: 'link', type: 'secondary', size: 'small', icon: 'rss', icon_placement: 'before', text: 'RSS Feed', url: url } only %}",
      '#context' => [
        'url' => $url,
      ],
    ];
  }

}
