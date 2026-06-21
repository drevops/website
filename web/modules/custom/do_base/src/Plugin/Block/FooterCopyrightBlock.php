<?php

declare(strict_types=1);

namespace Drupal\do_base\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the copyright line shown in the footer bottom bar.
 */
#[Block(
  id: 'do_base_footer_copyright',
  admin_label: new TranslatableMarkup('Footer copyright'),
  category: new TranslatableMarkup('DrevOps'),
)]
final class FooterCopyrightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('DrevOps © @year - Melbourne, Australia. Working across AU & NZ.', ['@year' => date('Y')]),
      // The rendered year changes daily at most; refresh once a day so a
      // cached page does not carry a stale year across a new year boundary.
      '#cache' => ['max-age' => 86400],
    ];
  }

}
