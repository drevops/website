<?php

/**
 * @file
 * Automated list component alterations.
 */

declare(strict_types=1);

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_civictheme_automated_list_view_alter().
 */
function drevops_civictheme_automated_list_view_alter(ViewExecutable $view): void {
  $settings = $view->component_settings ?? NULL;
  $paragraph = is_array($settings) ? ($settings['paragraph'] ?? NULL) : NULL;

  if (!$paragraph instanceof FieldableEntityInterface) {
    return;
  }

  if (!_drevops_automated_list_follows_page($paragraph)) {
    return;
  }

  $topics = _drevops_automated_list_page_topics();

  // The arguments are ordered content type, topics, site sections.
  // @see _civictheme_automated_list__update_view()
  $arguments = $view->args;

  // With the option on but no topic to match, the list would otherwise fall
  // back to 'all' and advertise the whole site as related.
  $arguments[1] = $topics === [] ? 'none' : implode('+', $topics);

  $view->setArguments($arguments);
}

/**
 * Tells whether a list takes its topics from the page it is placed on.
 */
function _drevops_automated_list_follows_page(FieldableEntityInterface $paragraph): bool {
  return (bool) civictheme_get_field_value($paragraph, 'field_c_p_list_topics_from_page');
}

/**
 * Implements template_preprocess_paragraph().
 */
function drevops_preprocess_paragraph__civictheme_automated_list(array &$variables): void {
  $paragraph = $variables['paragraph'] ?? NULL;

  if (!$paragraph instanceof FieldableEntityInterface) {
    return;
  }

  if (!_drevops_automated_list_follows_page($paragraph)) {
    return;
  }

  // The list is placed as a block on every page in its visibility list, so a
  // page whose topics nothing else shares has to collapse to nothing: the
  // heading and its spacing would otherwise render an empty section.
  if (_drevops_automated_list_is_empty($variables['rows'] ?? NULL)) {
    $variables['title'] = NULL;
    $variables['rows'] = NULL;
    $variables['vertical_spacing'] = NULL;
  }
}

/**
 * Tells whether a rendered view produced any rows.
 */
function _drevops_automated_list_is_empty(mixed $rows): bool {
  if (empty($rows)) {
    return TRUE;
  }

  return is_array($rows) && empty($rows['#view']->result ?? []) && empty($rows['view_build']['#view']->result ?? []);
}

/**
 * Collects the topic term ids of the page currently being viewed.
 *
 * @return int[]
 *   Term ids, empty when there is no node in the route or it carries none.
 */
function _drevops_automated_list_page_topics(): array {
  $route_match = \Drupal::routeMatch();
  $node = $route_match->getParameter('node_revision') ?: $route_match->getParameter('node');

  if (!$node instanceof FieldableEntityInterface || !$node->hasField('field_c_n_topics')) {
    return [];
  }

  $ids = [];

  foreach ($node->get('field_c_n_topics')->referencedEntities() as $term) {
    if ($term->access('view')) {
      $ids[] = (int) $term->id();
    }
  }

  return $ids;
}
