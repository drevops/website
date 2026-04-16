<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Plugin\GeneratedContent;

use Drupal\media\MediaInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\generated_content\Attribute\GeneratedContent;
use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Generated page nodes.
 *
 * @codeCoverageIgnore
 */
#[GeneratedContent(
  id: 'do_generated_content_node_civictheme_page',
  entity_type: 'node',
  bundle: 'civictheme_page',
  weight: 30,
  tracking: TRUE,
)]
class NodeCivicthemePage extends GeneratedContentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    for ($i = 0; $i < 10; $i++) {
      $title = sprintf('Generated page %s - %s', $i + 1, $this->helper::staticSentence(5));

      $components = $this->createComponents($i);

      $topic = $this->helper::randomTerm('civictheme_topics');
      $thumbnail = $this->helper::randomMediaItem('civictheme_image');

      $values = [
        'type' => 'civictheme_page',
        'title' => $title,
        'status' => 1,
        'field_c_n_summary' => $this->helper::staticPlainParagraph(),
        'field_c_n_vertical_spacing' => 'both',
        'field_c_n_banner_type' => 'default',
        'field_c_n_banner_theme' => 'light',
        'field_c_n_banner_blend_mode' => 'normal',
        'field_c_n_show_toc' => $this->helper::randomBool(30),
        'field_c_n_show_last_updated' => $this->helper::randomBool(50),
        'field_c_n_components' => $components,
      ];

      if ($topic instanceof TermInterface) {
        $values['field_c_n_topics'] = ['target_id' => $topic->id()];
      }

      if ($thumbnail instanceof MediaInterface) {
        $values['field_c_n_thumbnail'] = ['target_id' => $thumbnail->id()];
      }

      if ($this->helper::randomBool(30)) {
        $banner_image = $this->helper::randomMediaItem('civictheme_image');
        if ($banner_image instanceof MediaInterface) {
          $values['field_c_n_banner_background'] = ['target_id' => $banner_image->id()];
          $values['field_c_n_banner_type'] = 'large';
          $values['field_c_n_banner_theme'] = 'dark';
        }
      }

      $node = Node::create($values);
      $node->save();
      $entities[] = $node;

      $this->helper::log('Created page: %s', $title);
    }

    return $entities;
  }

  /**
   * Create paragraph components for a page.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph[]
   *   Array of saved paragraph entities.
   */
  protected function createComponents(int $index): array {
    $components = [];

    // Every page gets a content paragraph.
    $components[] = $this->createContentParagraph();

    // Alternate pages get additional components.
    if ($index % 2 === 0) {
      $components[] = $this->createAccordionParagraph();
    }

    if ($index % 3 === 0) {
      $components[] = $this->createPromoParagraph();
    }

    if ($index % 4 === 0) {
      $components[] = $this->createCalloutParagraph();
    }

    return $components;
  }

  /**
   * Create a content paragraph.
   */
  protected function createContentParagraph(): Paragraph {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_content',
      'field_c_p_theme' => $this->helper::randomBool() ? 'light' : 'dark',
      'field_c_p_vertical_spacing' => 'both',
      'field_c_p_content' => [
        'value' => $this->helper::staticRichText(3),
        'format' => 'civictheme_rich_text',
      ],
    ]);

    $paragraph->save();

    return $paragraph;
  }

  /**
   * Create an accordion paragraph with panels.
   */
  protected function createAccordionParagraph(): Paragraph {
    $panels = [];

    for ($i = 0; $i < 3; $i++) {
      $panel = Paragraph::create([
        'type' => 'civictheme_accordion_panel',
        'field_c_p_title' => sprintf('Section %s - %s', $i + 1, $this->helper::staticSentence(3)),
        'field_c_p_content' => [
          'value' => $this->helper::staticRichText(2),
          'format' => 'civictheme_rich_text',
        ],
        'field_c_p_expand' => $i === 0,
      ]);

      $panel->save();
      $panels[] = $panel;
    }

    $paragraph = Paragraph::create([
      'type' => 'civictheme_accordion',
      'field_c_p_theme' => 'light',
      'field_c_p_vertical_spacing' => 'both',
      'field_c_p_panels' => $panels,
    ]);

    $paragraph->save();

    return $paragraph;
  }

  /**
   * Create a promo paragraph.
   */
  protected function createPromoParagraph(): Paragraph {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_promo',
      'field_c_p_title' => $this->helper::staticSentence(4),
      'field_c_p_theme' => 'dark',
      'field_c_p_vertical_spacing' => 'both',
      'field_c_p_content' => [
        'value' => $this->helper::staticHtmlParagraph(),
        'format' => 'civictheme_rich_text',
      ],
      'field_c_p_link' => [
        'uri' => 'https://www.drevops.com.au',
        'title' => 'Learn more',
      ],
    ]);

    $paragraph->save();

    return $paragraph;
  }

  /**
   * Create a callout paragraph.
   */
  protected function createCalloutParagraph(): Paragraph {
    $paragraph = Paragraph::create([
      'type' => 'civictheme_callout',
      'field_c_p_title' => $this->helper::staticSentence(3),
      'field_c_p_theme' => 'light',
      'field_c_p_vertical_spacing' => 'both',
      'field_c_p_content' => [
        'value' => $this->helper::staticHtmlParagraph(),
        'format' => 'civictheme_rich_text',
      ],
      'field_c_p_links' => [
        [
          'uri' => 'https://www.drevops.com.au',
          'title' => 'Get started',
        ],
      ],
    ]);

    $paragraph->save();

    return $paragraph;
  }

}
