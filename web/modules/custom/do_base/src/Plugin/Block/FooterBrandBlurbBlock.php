<?php

declare(strict_types=1);

namespace Drupal\do_base\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the brand blurb shown in the first footer column.
 */
#[Block(
  id: 'do_base_footer_brand_blurb',
  admin_label: new TranslatableMarkup('Footer brand blurb'),
  category: new TranslatableMarkup('DrevOps'),
)]
final class FooterBrandBlurbBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('A technical digital agency that builds and supports reliable websites, engineered properly. Direct with you, or as the technical partner behind your agency.'),
    ];
  }

}
