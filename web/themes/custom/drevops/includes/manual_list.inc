<?php

/**
 * @file
 * Manual list paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
function drevops_preprocess_paragraph__civictheme_manual_list(array &$variables): void {
  _drevops_preprocess_paragraph__paragraph_field__list_layout($variables);

  // Extra calculations for the Spotlight layout.
  // Last "hanging" items must be stretched full width if the "fill_width"
  // option is enabled.
  if ($variables['layout'] === 'spotlight'
    && $variables['column_count'] > 1
    // There must be more items than are needed for two grid row. Two - because
    // first element always occupies height on two rows.
    && ($variables['column_count'] * 2) <= count($variables['rows'])
    && $variables['fill_width']
  ) {
    $columns_count = $variables['column_count'];
    $rows = &$variables['rows'];

    // The first item spans two rows in the spotlight layout, which affects cell
    // counting.
    $occupied_cells_amount = count($rows) + 1;

    // Checks "hanging" items presence.
    if ($occupied_cells_amount % $columns_count !== 0) {
      $hanged_items = $occupied_cells_amount % $columns_count;
      $last_line_items = array_splice($rows, $hanged_items * -1);
      $last_line_items_count = count($last_line_items);

      // Wraps "hanging" items with a "col" element before adding them to a
      // separate grid row.
      array_walk($last_line_items, function (&$item) use ($variables): void {
        $item = [
          '#type' => 'html_tag',
          '#tag' => $variables['column_element'] ?? 'div',
          '#attributes' => [
            'class' => [
              'col',
              $variables['column_class'] ?? '',
            ],
          ],
          0 => $item,
        ];
      });

      // Prepares last row element with hanging items.
      $last_line_render = $last_line_items +
        [
          '#row_classes' => implode(
            ' ',
            [
              'last-row-grid',
              'grid-l-' . $last_line_items_count . '-1',
              'grid-xxs-1-1',
              'col-l-' . $columns_count,
              'col-xxs-' . 1,
            ]),
        ];

      // Adds the "last row" to the Items list.
      $rows[] = $last_line_render;
    }
  }
}
