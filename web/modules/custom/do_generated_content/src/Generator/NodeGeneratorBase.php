<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

use Drupal\generated_content\Plugin\GeneratedContent\GeneratedContentPluginBase;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\TermInterface;

/**
 * Base class for node generators.
 *
 * Holds the field set that CivicTheme attaches to every page-like bundle, so
 * a bundle generator only states what makes it different.
 */
abstract class NodeGeneratorBase extends GeneratedContentPluginBase {

  use FieldAllowedValuesTrait;
  use VocabularyTermsTrait;

  /**
   * Number of nodes each bundle generates.
   */
  public const COUNT = 20;

  /**
   * Number of components each page-like node carries.
   */
  protected const COMPONENTS_PER_NODE = 3;

  /**
   * Human-readable bundle name used in titles and log lines.
   */
  protected const LABEL = 'node';

  /**
   * Moderation states a run covers, the first of which most nodes take.
   */
  public const MODERATION_STATES = ['published', 'draft', 'needs_review', 'archived'];

  /**
   * The component generator.
   */
  protected ?ComponentGenerator $componentGenerator = NULL;

  /**
   * How many topics have been assigned so far.
   */
  protected int $topicsAssigned = 0;

  /**
   * {@inheritdoc}
   */
  public function generate(): array {
    $entities = [];

    for ($index = 0; $index < static::COUNT; $index++) {
      $values = [
        'type' => $this->getBundle(),
        'title' => $this->title($index),
        'moderation_state' => $this->moderationState($index),
      ] + $this->bundleValues($index);

      $node = Node::create($values);
      $node->save();

      $entities[] = $node;

      $this->helper::log('Created %s: %s (%s)', static::LABEL, $node->label(), $values['moderation_state']);
    }

    $this->logCoverage();

    return $entities;
  }

  /**
   * Build the field values specific to this bundle.
   *
   * @param int $index
   *   Zero-based run index.
   *
   * @return array
   *   Field values, excluding type, title and moderation state.
   */
  abstract protected function bundleValues(int $index): array;

  /**
   * Build the node title.
   */
  protected function title(int $index): string {
    return sprintf('Generated %s %s - %s', static::LABEL, $index + 1, $this->helper::staticSentence(5));
  }

  /**
   * Pick the moderation state for a run index.
   *
   * Most nodes are published so an anonymous visitor still sees a populated
   * site, with one node in each of the remaining states.
   */
  protected function moderationState(int $index): string {
    $states = static::MODERATION_STATES;
    $default = array_shift($states);
    $first_non_default = static::COUNT - count($states);

    return $index >= $first_non_default ? $states[$index - $first_non_default] : $default;
  }

  /**
   * Build the fields every bundle with a summary and topics shares.
   *
   * @param int $index
   *   Zero-based run index.
   *
   * @return array
   *   Field values.
   */
  protected function commonValues(int $index): array {
    $values = [
      'field_c_n_vertical_spacing' => $this->nodeOption('field_c_n_vertical_spacing', $index),
      'field_c_n_show_toc' => CaseMatrix::bit($index, 0),
      'field_c_n_show_last_updated' => CaseMatrix::bit($index, 1),
    ];

    if (CaseMatrix::bit($index, 2)) {
      $values['field_c_n_summary'] = $this->helper::staticPlainParagraph();
    }

    if (CaseMatrix::bit($index, 3)) {
      $values['field_c_n_custom_last_updated'] = RelativeDate::format('-3 days');
    }

    $thumbnail = $this->helper::randomMediaItem('civictheme_image');

    if (CaseMatrix::bit($index, 4) && $thumbnail instanceof MediaInterface) {
      $values['field_c_n_thumbnail'] = ['target_id' => $thumbnail->id()];
    }

    $sections = $this->vocabularyTerms('civictheme_site_sections');

    if (CaseMatrix::bit($index, 0) && $sections !== []) {
      $values['field_c_n_site_section'] = ['target_id' => CaseMatrix::cycle($sections, $index)->id()];
    }

    $topics = $this->topics(CaseMatrix::cycle([0, 1, 3], $index));

    if ($topics !== []) {
      $values['field_c_n_topics'] = array_map(static fn(TermInterface $term): array => ['target_id' => $term->id()], $topics);
    }

    return $values;
  }

  /**
   * Build the banner fields CivicTheme attaches to page-like bundles.
   *
   * @param int $index
   *   Zero-based run index.
   *
   * @return array
   *   Field values.
   */
  protected function bannerValues(int $index): array {
    $values = [
      'field_c_n_banner_type' => $this->nodeOption('field_c_n_banner_type', $index),
      'field_c_n_banner_theme' => $this->nodeOption('field_c_n_banner_theme', $index, 1),
      'field_c_n_banner_blend_mode' => $this->nodeOption('field_c_n_banner_blend_mode', $index),
      'field_c_n_banner_hide_breadcrumb' => CaseMatrix::bit($index, 2),
    ];

    if (CaseMatrix::bit($index, 3)) {
      $values['field_c_n_banner_title'] = $this->helper::staticSentence(4);
    }

    $background = $this->helper::randomMediaItem('civictheme_image');

    if (CaseMatrix::bit($index, 1) && $background instanceof MediaInterface) {
      $values['field_c_n_banner_background'] = ['target_id' => $background->id()];
    }

    $featured = $this->helper::randomMediaItem('civictheme_image');

    if (CaseMatrix::bit($index, 4) && $featured instanceof MediaInterface) {
      $values['field_c_n_banner_featured_image'] = ['target_id' => $featured->id()];
    }

    if (CaseMatrix::bit($index, 0)) {
      $values['field_c_n_banner_components'] = $this->components($index, 'field_c_n_banner_components', 1);
    }

    if (CaseMatrix::bit($index, 2)) {
      $values['field_c_n_banner_components_bott'] = $this->components($index + 1, 'field_c_n_banner_components_bott', 1);
    }

    return $values;
  }

  /**
   * Build the sidebar, tag and component fields of a page-like bundle.
   *
   * @param int $index
   *   Zero-based run index.
   *
   * @return array
   *   Field values.
   */
  protected function pageValues(int $index): array {
    return [
      'field_c_n_hide_sidebar' => CaseMatrix::bit($index, 3),
      'field_c_n_hide_tags' => CaseMatrix::bit($index, 4),
      'field_c_n_components' => $this->components($index, 'field_c_n_components', static::COMPONENTS_PER_NODE),
    ];
  }

  /**
   * Build components for a node, walking the bundles the field accepts.
   *
   * @param int $index
   *   Zero-based run index.
   * @param string $field_name
   *   Name of the component field to fill.
   * @param int $count
   *   How many components to build.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph[]
   *   Saved paragraphs.
   */
  protected function components(int $index, string $field_name, int $count): array {
    $bundles = $this->generator()->allowedTargetBundles('node', $this->getBundle(), $field_name);

    $components = [];

    for ($i = 0; $i < $count; $i++) {
      $bundle = (string) CaseMatrix::cycle($bundles, $index * $count + $i);
      $component = $this->generator()->create($bundle, $index + $i);

      if (!$component instanceof Paragraph) {
        $this->helper::log('Skipped %s component: no entity to reference yet.', $bundle);

        continue;
      }

      $components[] = $component;
    }

    return $components;
  }

  /**
   * Pick the next topics from the vocabulary.
   *
   * Walks a counter rather than the run index so a run keeps the whole
   * vocabulary in play instead of revisiting its first few terms.
   *
   * @param int $count
   *   How many topics to pick.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The picked terms.
   */
  protected function topics(int $count): array {
    $terms = $this->vocabularyTerms('civictheme_topics');

    $picked = [];

    for ($i = 0; $terms !== [] && $i < $count; $i++) {
      $picked[] = CaseMatrix::cycle($terms, $this->topicsAssigned++);
    }

    return $picked;
  }

  /**
   * Walk a node field's allowed values.
   *
   * @param string $field_name
   *   Name of the list field to read allowed values from.
   * @param int $index
   *   Zero-based run index.
   * @param int $offset
   *   Shifts where the walk starts.
   *
   * @return string
   *   The allowed value for this index.
   */
  protected function nodeOption(string $field_name, int $index, int $offset = 0): string {
    return (string) CaseMatrix::cycle($this->allowedValues('node', $this->getBundle(), $field_name), $index, $offset);
  }

  /**
   * Get the component generator.
   */
  protected function generator(): ComponentGenerator {
    if (!$this->componentGenerator instanceof ComponentGenerator) {
      $this->componentGenerator = new ComponentGenerator($this->entityTypeManager, $this->helper, $this->repository);
    }

    return $this->componentGenerator;
  }

  /**
   * Report which component bundles the run produced.
   */
  protected function logCoverage(): void {
    $bundles = $this->generator()->createdBundles();

    if ($bundles === []) {
      return;
    }

    $this->helper::log('Covered %s component bundles for %s: %s', count($bundles), static::LABEL, implode(', ', $bundles));
  }

}
