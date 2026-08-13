<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\generated_content\GeneratedContentRepository;
use Drupal\generated_content\Helpers\GeneratedContentHelper;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs_library\Entity\LibraryItem;
use Drupal\taxonomy\TermInterface;

/**
 * Builds one saved paragraph of any component bundle the site allows.
 *
 * @codeCoverageIgnore
 */
final class ComponentGenerator {

  use FieldAllowedValuesTrait;
  use VocabularyTermsTrait;

  /**
   * Library item reused by every generated 'from_library' component.
   */
  protected ?LibraryItem $libraryItem = NULL;

  /**
   * Bundles built so far, keyed by bundle name.
   *
   * @var array<string, string>
   */
  protected array $createdBundles = [];

  /**
   * How many manual lists have been built.
   *
   * The card walk counts lists rather than reusing the host node's index:
   * only a few node indexes carry a manual list, so walking on that index
   * revisits the same handful of card bundles and never reaches the rest.
   */
  protected int $manualListCount = 0;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GeneratedContentHelper $generatedContentHelper,
    protected GeneratedContentRepository $generatedContentRepository,
  ) {}

  /**
   * Bundles this generator can build.
   *
   * @return string[]
   *   Paragraph bundle names.
   */
  public static function supportedBundles(): array {
    return [
      'civictheme_accordion',
      'civictheme_accordion_panel',
      'civictheme_attachment',
      'civictheme_automated_list',
      'civictheme_callout',
      'civictheme_campaign',
      'civictheme_content',
      'civictheme_event_card',
      'civictheme_event_card_ref',
      'civictheme_fast_fact_card',
      'civictheme_iframe',
      'civictheme_manual_list',
      'civictheme_map',
      'civictheme_message',
      'civictheme_navigation_card',
      'civictheme_navigation_card_ref',
      'civictheme_next_step',
      'civictheme_promo',
      'civictheme_promo_card',
      'civictheme_promo_card_ref',
      'civictheme_publication_card',
      'civictheme_service_card',
      'civictheme_slider',
      'civictheme_slider_slide',
      'civictheme_slider_slide_ref',
      'civictheme_snippet',
      'civictheme_snippet_ref',
      'civictheme_subject_card',
      'civictheme_subject_card_ref',
      'civictheme_webform',
      'divider',
      'from_library',
      'logo_strip',
      'steps',
      'steps_item',
    ];
  }

  /**
   * Build and save one component paragraph.
   *
   * @param string $bundle
   *   Paragraph bundle to build.
   * @param int $index
   *   Zero-based run index, used to walk each field's values.
   * @param int $position
   *   Zero-based position within its parent, for bundles that are numbered or
   *   whose first item behaves differently.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph|null
   *   The saved paragraph, or NULL when the bundle needs an entity that does
   *   not exist yet - a reference card before any node has been generated.
   */
  public function create(string $bundle, int $index, int $position = 0): ?Paragraph {
    $values = match ($bundle) {
      'civictheme_accordion' => $this->accordion($index),
      'civictheme_accordion_panel' => $this->accordionPanel($position),
      'civictheme_attachment' => $this->attachment($index),
      'civictheme_automated_list' => $this->automatedList($index),
      'civictheme_callout' => $this->callout($index),
      'civictheme_campaign' => $this->campaign($index),
      'civictheme_content' => $this->content($index),
      'civictheme_event_card' => $this->eventCard($index),
      'civictheme_event_card_ref' => $this->reference($bundle, $index, 'civictheme_event'),
      'civictheme_fast_fact_card' => $this->fastFactCard($index),
      'civictheme_iframe' => $this->iframe($index),
      'civictheme_manual_list' => $this->manualList($index),
      'civictheme_map' => $this->map($index),
      'civictheme_message' => $this->message($index),
      'civictheme_navigation_card' => $this->navigationCard($index),
      'civictheme_navigation_card_ref' => $this->reference($bundle, $index, 'civictheme_page'),
      'civictheme_next_step' => $this->nextStep($index),
      'civictheme_promo' => $this->promo($index),
      'civictheme_promo_card' => $this->promoCard($index),
      'civictheme_promo_card_ref' => $this->reference($bundle, $index, 'civictheme_page'),
      'civictheme_publication_card' => $this->publicationCard($index),
      'civictheme_service_card' => $this->serviceCard($index),
      'civictheme_slider' => $this->slider($index),
      'civictheme_slider_slide' => $this->sliderSlide($index),
      'civictheme_slider_slide_ref' => $this->sliderSlideReference($index),
      'civictheme_snippet' => $this->snippet($index),
      'civictheme_snippet_ref' => $this->reference($bundle, $index, 'civictheme_page'),
      'civictheme_subject_card' => $this->subjectCard($index),
      'civictheme_subject_card_ref' => $this->reference($bundle, $index, 'civictheme_page'),
      'civictheme_webform' => $this->webform($index),
      'divider' => $this->divider($index),
      'from_library' => $this->fromLibrary(),
      'logo_strip' => $this->logoStrip($index),
      'steps' => $this->steps($index),
      'steps_item' => $this->stepsItem($position),
      default => throw new \InvalidArgumentException(sprintf('Unsupported component bundle %s.', $bundle)),
    };

    if ($values === NULL) {
      return NULL;
    }

    $paragraph = Paragraph::create(['type' => $bundle] + $values);
    $paragraph->save();

    $this->createdBundles[$bundle] = $bundle;

    return $paragraph;
  }

  /**
   * Bundles built so far, at every nesting level.
   *
   * @return string[]
   *   Bundle names, sorted.
   */
  public function createdBundles(): array {
    $bundles = $this->createdBundles;
    ksort($bundles);

    return array_values($bundles);
  }

  /**
   * Build several paragraphs of one bundle.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph[]
   *   Saved paragraphs, excluding any the generator had to skip.
   */
  protected function createMany(string $bundle, int $index, int $count): array {
    $paragraphs = [];

    for ($i = 0; $i < $count; $i++) {
      $paragraph = $this->create($bundle, $index + $i, $i);

      if ($paragraph instanceof Paragraph) {
        $paragraphs[] = $paragraph;
      }
    }

    return $paragraphs;
  }

  /**
   * Walk a paragraph field's allowed values.
   */
  protected function option(string $bundle, string $field_name, int $index, int $offset = 0): string {
    return (string) CaseMatrix::cycle($this->allowedValues('paragraph', $bundle, $field_name), $index, $offset);
  }

  /**
   * Build the theme and vertical spacing every component shares.
   *
   * @return array<string, string>
   *   Field values.
   */
  protected function chrome(string $bundle, int $index): array {
    return [
      'field_c_p_theme' => $this->option($bundle, 'field_c_p_theme', $index),
      'field_c_p_vertical_spacing' => $this->option($bundle, 'field_c_p_vertical_spacing', $index, 1),
    ];
  }

  /**
   * Build a rich text field value.
   *
   * @return array<string, string>
   *   Field value.
   */
  protected function richText(int $paragraphs = 2): array {
    return [
      'value' => $this->generatedContentHelper::staticRichText($paragraphs),
      'format' => Formats::TEXT,
    ];
  }

  /**
   * Build a link field value.
   *
   * @return array<string, string>
   *   Field value.
   */
  protected function link(string $title): array {
    return ['uri' => 'internal:/', 'title' => $title];
  }

  /**
   * Get a generated image, when any exist.
   */
  protected function image(): ?MediaInterface {
    $media = $this->generatedContentHelper::randomMediaItem('civictheme_image');

    return $media instanceof MediaInterface ? $media : NULL;
  }

  /**
   * Build an accordion with panels.
   */
  protected function accordion(int $index): array {
    return $this->chrome('civictheme_accordion', $index) + [
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_c_p_expand' => CaseMatrix::bit($index, 1),
      'field_c_p_panels' => $this->createMany('civictheme_accordion_panel', $index, 3),
    ];
  }

  /**
   * Build a single accordion panel.
   */
  protected function accordionPanel(int $position): array {
    return [
      'field_c_p_title' => sprintf('Panel %s - %s', $position + 1, $this->generatedContentHelper::staticSentence(3)),
      'field_c_p_content' => $this->richText(),
      // The first panel of a set opens so the component is not rendered fully
      // collapsed on every page.
      'field_c_p_expand' => $position === 0,
    ];
  }

  /**
   * Build an attachment listing document media.
   */
  protected function attachment(int $index): array {
    $documents = $this->generatedContentHelper::randomMediaItems('civictheme_document', 2);

    return $this->chrome('civictheme_attachment', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 2),
      'field_c_p_attachments' => array_map(static fn(MediaInterface $media): array => ['target_id' => $media->id()], $documents),
    ];
  }

  /**
   * Build an automated list.
   */
  protected function automatedList(int $index): array {
    return $this->chrome('civictheme_automated_list', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_c_p_list_content_type' => $this->option('civictheme_automated_list', 'field_c_p_list_content_type', $index),
      'field_c_p_list_type' => $this->option('civictheme_automated_list', 'field_c_p_list_type', $index),
      'field_c_p_list_item_view_as' => $this->option('civictheme_automated_list', 'field_c_p_list_item_view_as', $index),
      'field_c_p_list_item_theme' => $this->option('civictheme_automated_list', 'field_c_p_list_item_theme', $index),
      'field_c_p_list_limit_type' => $this->option('civictheme_automated_list', 'field_c_p_list_limit_type', $index),
      'field_c_p_list_filters_exp' => $this->option('civictheme_automated_list', 'field_c_p_list_filters_exp', $index),
      'field_c_p_list_column_count' => $this->option('civictheme_automated_list', 'field_c_p_list_column_count', $index),
      'field_c_p_list_fill_width' => CaseMatrix::bit($index, 1),
      // Shares bit 0 rather than taking a high bit of its own: this component
      // is itself placed on only a few nodes of a run, so a high bit turns on
      // for a list that reaches a page too rarely to be worth looking at.
      'field_c_p_list_topics_from_page' => CaseMatrix::bit($index, 0),
      'field_c_p_list_limit' => CaseMatrix::cycle([3, 6, 9], $index),
      'field_c_p_list_link_above' => $this->link('View all'),
      'field_c_p_list_link_below' => $this->link('See more'),
    ];
  }

  /**
   * Build a callout.
   */
  protected function callout(int $index): array {
    return $this->chrome('civictheme_callout', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_links' => [$this->link('Get started'), $this->link('Contact us')],
    ];
  }

  /**
   * Build a campaign.
   */
  protected function campaign(int $index): array {
    $image = $this->image();

    $values = $this->chrome('civictheme_campaign', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_date' => RelativeDate::format('-7 days'),
      'field_c_p_image_position' => $this->option('civictheme_campaign', 'field_c_p_image_position', $index),
      'field_c_p_links' => [$this->link('Read the campaign')],
      'field_c_p_topics' => $this->topics($index),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a content component.
   */
  protected function content(int $index): array {
    return $this->chrome('civictheme_content', $index) + [
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_c_p_content' => $this->richText(3),
    ];
  }

  /**
   * Build an event card.
   */
  protected function eventCard(int $index): array {
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_event_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
      'field_c_p_location' => 'Melbourne, Victoria',
      'field_c_p_link' => $this->link('View event'),
      'field_c_p_topics' => $this->topics($index),
      'field_c_p_date_range' => [
        'value' => RelativeDate::format('+7 days', Formats::DATETIME),
        'end_value' => RelativeDate::format('+7 days +2 hours', Formats::DATETIME),
      ],
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a fast fact card.
   */
  protected function fastFactCard(int $index): array {
    $icon = $this->generatedContentHelper::randomMediaItem('civictheme_icon');

    $values = [
      'field_c_p_theme' => $this->option('civictheme_fast_fact_card', 'field_c_p_theme', $index),
      'field_c_p_title' => sprintf('%s%%', ($index + 1) * 5),
      'field_c_p_summary' => $this->generatedContentHelper::staticSentence(6),
      'field_c_p_link' => $this->link('Read the detail'),
    ];

    if ($icon instanceof MediaInterface) {
      $values['field_c_p_icon'] = ['target_id' => $icon->id()];
    }

    return $values;
  }

  /**
   * Build an iframe.
   */
  protected function iframe(int $index): array {
    return $this->chrome('civictheme_iframe', $index) + [
      'field_c_p_background' => CaseMatrix::bit($index, 1),
      // Generated content stays self-contained, so the frame points at the
      // site rather than a third-party embed.
      'field_c_p_url' => '/',
      'field_c_p_width' => '100%',
      'field_c_p_height' => CaseMatrix::cycle(['400px', '600px'], $index),
    ];
  }

  /**
   * Build a manual list, walking every card bundle it accepts.
   */
  protected function manualList(int $index): array {
    // Drop the host bundle: a field configured to accept it would otherwise
    // recurse into this builder until the stack runs out.
    $bundles = array_values(array_diff($this->allowedTargetBundles('paragraph', 'civictheme_manual_list', 'field_c_p_list_items'), ['civictheme_manual_list']));

    $items = [];

    // Walk three consecutive card bundles so a run of lists covers all of
    // them rather than repeating the same card everywhere.
    for ($i = 0; $bundles !== [] && $i < 3; $i++) {
      $item = $this->create((string) CaseMatrix::cycle($bundles, $this->manualListCount * 3 + $i), $index + $i);

      if ($item instanceof Paragraph) {
        $items[] = $item;
      }
    }

    $this->manualListCount++;

    return $this->chrome('civictheme_manual_list', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 2),
      'field_c_p_list_items' => $items,
      'field_c_p_list_column_count' => $this->option('civictheme_manual_list', 'field_c_p_list_column_count', $index),
      'field_c_p_list_fill_width' => CaseMatrix::bit($index, 3),
      'field_p_list_layout' => $this->option('civictheme_manual_list', 'field_p_list_layout', $index),
      'field_c_p_list_link_above' => $this->link('View all'),
      'field_c_p_list_link_below' => $this->link('See more'),
    ];
  }

  /**
   * Build a map.
   */
  protected function map(int $index): array {
    return $this->chrome('civictheme_map', $index) + [
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_c_p_address' => '121 Exhibition Street, Melbourne VIC 3000',
      'field_c_p_embed_url' => $this->link('Map'),
      'field_c_p_view_link' => $this->link('View larger map'),
    ];
  }

  /**
   * Build a message.
   */
  protected function message(int $index): array {
    return $this->chrome('civictheme_message', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 1),
      'field_c_p_message_type' => $this->option('civictheme_message', 'field_c_p_message_type', $index),
    ];
  }

  /**
   * Build a navigation card.
   */
  protected function navigationCard(int $index): array {
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_navigation_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
      'field_c_p_link' => $this->link('Go to section'),
      'field_c_p_show_image_as_icon' => CaseMatrix::bit($index, 0),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a next step.
   */
  protected function nextStep(int $index): array {
    return $this->chrome('civictheme_next_step', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_link' => $this->link('Take the next step'),
    ];
  }

  /**
   * Build a promo.
   */
  protected function promo(int $index): array {
    return $this->chrome('civictheme_promo', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 2),
      'field_c_p_link' => $this->link('Learn more'),
    ];
  }

  /**
   * Build a promo card.
   */
  protected function promoCard(int $index): array {
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_promo_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_subtitle' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
      'field_c_p_link' => $this->link('Read more'),
      'field_c_p_topics' => $this->topics($index),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a publication card.
   */
  protected function publicationCard(int $index): array {
    $document = $this->generatedContentHelper::randomMediaItem('civictheme_document');
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_publication_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(5),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
    ];

    if ($document instanceof MediaInterface) {
      $values['field_c_p_document'] = ['target_id' => $document->id()];
    }

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a service card.
   */
  protected function serviceCard(int $index): array {
    return [
      'field_c_p_theme' => $this->option('civictheme_service_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_links' => [$this->link('Apply online'), $this->link('Check eligibility')],
    ];
  }

  /**
   * Build a slider with slides.
   */
  protected function slider(int $index): array {
    $slides = $this->createMany('civictheme_slider_slide', $index, 2);
    $slide_reference = $this->create('civictheme_slider_slide_ref', $index);

    if ($slide_reference instanceof Paragraph) {
      $slides[] = $slide_reference;
    }

    return $this->chrome('civictheme_slider', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_background' => CaseMatrix::bit($index, 3),
      'field_c_p_slides' => $slides,
    ];
  }

  /**
   * Build a slider slide.
   */
  protected function sliderSlide(int $index): array {
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_slider_slide', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_date' => RelativeDate::format('-14 days'),
      'field_c_p_image_position' => $this->option('civictheme_slider_slide', 'field_c_p_image_position', $index),
      'field_c_p_links' => [$this->link('Find out more')],
      'field_c_p_topics' => $this->topics($index),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a slide referencing a node.
   */
  protected function sliderSlideReference(int $index): ?array {
    $values = $this->reference('civictheme_slider_slide_ref', $index, 'civictheme_page');

    if ($values === NULL) {
      return NULL;
    }

    return $values + [
      'field_c_p_link_text' => 'Read the page',
      'field_c_p_image_position' => $this->option('civictheme_slider_slide_ref', 'field_c_p_image_position', $index),
    ];
  }

  /**
   * Build a snippet.
   */
  protected function snippet(int $index): array {
    return [
      'field_c_p_theme' => $this->option('civictheme_snippet', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
      'field_c_p_link' => $this->link('Read the snippet'),
      'field_c_p_topics' => $this->topics($index),
    ];
  }

  /**
   * Build a subject card.
   */
  protected function subjectCard(int $index): array {
    $image = $this->image();

    $values = [
      'field_c_p_theme' => $this->option('civictheme_subject_card', 'field_c_p_theme', $index),
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(3),
      'field_c_p_link' => $this->link('Browse subject'),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a webform component.
   */
  protected function webform(int $index): array {
    return $this->chrome('civictheme_webform', $index) + [
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_c_p_webform' => ['target_id' => CaseMatrix::cycle(['civictheme_enquiry', 'civictheme_feedback'], $index)],
    ];
  }

  /**
   * Build a divider.
   */
  protected function divider(int $index): array {
    $image = $this->image();

    $values = $this->chrome('divider', $index) + [
      'field_p_alignment' => $this->option('divider', 'field_p_alignment', $index),
      'field_p_size' => $this->option('divider', 'field_p_size', $index),
    ];

    if ($image instanceof MediaInterface) {
      $values['field_c_p_image'] = ['target_id' => $image->id()];
    }

    return $values;
  }

  /**
   * Build a component pointing at a reusable library item.
   */
  protected function fromLibrary(): array {
    return ['field_reusable_paragraph' => ['target_id' => $this->libraryItem()->id()]];
  }

  /**
   * Build a logo strip.
   *
   * @return array|null
   *   Field values, or NULL when no image has been generated yet: a strip with
   *   no logos renders nothing, and its logos field is required.
   */
  protected function logoStrip(int $index): ?array {
    $logos = $this->generatedContentHelper::randomMediaItems('civictheme_image', 8);

    if ($logos === []) {
      return NULL;
    }

    return $this->chrome('logo_strip', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 0),
      'field_p_logos' => array_map(static fn(MediaInterface $media): array => ['target_id' => $media->id()], $logos),
    ];
  }

  /**
   * Build a steps component with items.
   */
  protected function steps(int $index): array {
    return $this->chrome('steps', $index) + [
      'field_c_p_title' => $this->generatedContentHelper::staticSentence(4),
      'field_c_p_content' => $this->richText(1),
      'field_c_p_background' => CaseMatrix::bit($index, 1),
      'field_c_p_list_items' => $this->createMany('steps_item', $index, 3),
    ];
  }

  /**
   * Build a single step.
   */
  protected function stepsItem(int $position): array {
    return [
      'field_c_p_title' => sprintf('Step %s - %s', $position + 1, $this->generatedContentHelper::staticSentence(3)),
      'field_c_p_summary' => $this->generatedContentHelper::staticPlainParagraph(),
      'field_p_receive' => $this->generatedContentHelper::staticSentence(8),
    ];
  }

  /**
   * Build a paragraph that references a node of the given bundle.
   *
   * @return array|null
   *   Field values, or NULL when no node of that bundle exists yet.
   */
  protected function reference(string $bundle, int $index, string $node_bundle): ?array {
    $node = $this->referencedNode($node_bundle);

    if (!$node instanceof NodeInterface) {
      return NULL;
    }

    return [
      'field_c_p_theme' => $this->option($bundle, 'field_c_p_theme', $index),
      'field_c_p_reference' => ['target_id' => $node->id()],
    ];
  }

  /**
   * Get a node a reference component can point at.
   *
   * Prefers a generated node so a fresh site still produces every reference
   * component: 'randomRealNode' excludes everything the repository tracks, so
   * on a site with no pre-existing content of the bundle it finds nothing.
   */
  protected function referencedNode(string $node_bundle): ?NodeInterface {
    $node = $this->generatedContentHelper::randomNode($node_bundle) ?? $this->generatedContentHelper::randomRealNode($node_bundle);

    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Build topic references.
   *
   * @return array<int, array<string, int|string|null>>
   *   Field values.
   */
  protected function topics(int $index): array {
    $terms = $this->vocabularyTerms('civictheme_topics');

    if ($terms === []) {
      return [];
    }

    return array_map(
      static fn(TermInterface $term): array => ['target_id' => $term->id()],
      CaseMatrix::subset($terms, $index, [1, 2])
    );
  }

  /**
   * Get the shared library item, creating it on first use.
   */
  protected function libraryItem(): LibraryItem {
    if ($this->libraryItem instanceof LibraryItem) {
      return $this->libraryItem;
    }

    $paragraph = Paragraph::create([
      'type' => 'civictheme_content',
      'field_c_p_theme' => 'light',
      'field_c_p_vertical_spacing' => 'both',
      'field_c_p_content' => $this->richText(2),
    ]);
    $paragraph->save();

    $library_item = LibraryItem::create([
      'label' => 'Generated reusable content',
      'paragraphs' => $paragraph,
    ]);
    $library_item->save();

    // A library item outlives the node that referenced it, and deleting it
    // leaves its paragraph behind, so both are tracked to be removed with the
    // rest of the generated content.
    $this->generatedContentRepository->addEntities([$library_item, $paragraph]);

    $this->libraryItem = $library_item;

    return $library_item;
  }

  /**
   * Get the bundles an entity reference field accepts.
   *
   * @return string[]
   *   Target bundle names, in the order the field declares them.
   */
  public function allowedTargetBundles(string $entity_type, string $bundle, string $field_name): array {
    $id = $entity_type . '.' . $bundle . '.' . $field_name;
    $field = $this->entityTypeManager->getStorage('field_config')->load($id);

    if (!$field instanceof FieldConfigInterface) {
      throw new \RuntimeException(sprintf('Field %s does not exist.', $id));
    }

    $handler_settings = $field->getSetting('handler_settings') ?? [];

    return array_map(strval(...), array_keys($handler_settings['target_bundles'] ?? []));
  }

}
