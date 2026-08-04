<?php

/**
 * @file
 * Project node functions.
 */

declare(strict_types=1);

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Pre-process Project nodes.
 *
 * CivicTheme resolves full-view node preprocessing by bundle name, calling
 * `_civictheme_preprocess_node__<bundle>__full()` when such a function exists,
 * so each content type owns its own full-view theming.
 */
function _civictheme_preprocess_node__project__full(array &$variables): void {
  $node = $variables['node'] ?? NULL;

  if (!$node instanceof NodeInterface) {
    return;
  }

  // The page title is rendered by the page template, so leaving the node label
  // in place would repeat it on revision pages.
  unset($variables['label']);

  // Each of these carries its own render weight, so the call order here does
  // not decide where the section ends up on the page.
  _drevops_project_add_contributions($node, $variables);
  _drevops_node_add_topic_tags($node, $variables);
  _drevops_project_add_at_a_glance($node, $variables);
  _drevops_node_add_table_of_contents($node, $variables);
}

/**
 * Prepends the "At a glance" panel to a project's content.
 */
function _drevops_project_add_at_a_glance(NodeInterface $node, array &$variables): void {
  $rows = _drevops_project_at_a_glance_rows($node, $variables);

  if ($rows === []) {
    return;
  }

  array_unshift($variables['content'], [
    '#type' => 'component',
    '#component' => 'drevops:at-a-glance',
    '#props' => [
      'title' => (string) t('At a glance'),
      'rows' => $rows,
      'vertical_spacing' => 'both',
    ],
    '#weight' => -90,
  ]);
}

/**
 * Appends the open source contributions list to a project's content.
 */
function _drevops_project_add_contributions(NodeInterface $node, array &$variables): void {
  $links = _drevops_project_link_values($node, 'field_do_n_oss_contributions');

  if ($links === []) {
    return;
  }

  $variables['content'][] = [
    '#type' => 'component',
    '#component' => 'drevops:link-list',
    '#props' => [
      'title' => (string) t('Open source contributions'),
      'links' => $links,
      'vertical_spacing' => 'both',
    ],
    '#weight' => 90,
  ];
}

/**
 * Builds the label and value rows of the "At a glance" panel.
 *
 * A row whose field holds no value is left out entirely, so a project that
 * cannot name its client or its live site renders no empty label.
 */
function _drevops_project_at_a_glance_rows(NodeInterface $node, array &$variables): array {
  $fields = [
    'field_do_n_client' => ['label' => t('Client'), 'resolver' => 'scalar'],
    'field_do_n_role' => ['label' => t('Role'), 'resolver' => 'scalar'],
    'field_do_n_delivered_at' => ['label' => t('Delivered at'), 'resolver' => 'scalar'],
    'field_do_n_year' => ['label' => t('Year'), 'resolver' => 'scalar'],
    'field_do_n_status' => ['label' => t('Status'), 'resolver' => 'list'],
    'field_do_n_live_url' => ['label' => t('Live site'), 'resolver' => 'link'],
    'field_do_n_sector' => ['label' => t('Sector'), 'resolver' => 'entities'],
    'field_do_n_services' => ['label' => t('Services'), 'resolver' => 'entities'],
    'field_do_n_technologies' => ['label' => t('Technologies'), 'resolver' => 'entities'],
  ];

  $rows = [];

  foreach ($fields as $field_name => $info) {
    if (!$node->hasField($field_name)) {
      continue;
    }

    $values = match ($info['resolver']) {
      'scalar' => _drevops_project_scalar_values($node, $field_name),
      'list' => _drevops_project_list_values($node, $field_name),
      'link' => _drevops_project_link_values($node, $field_name),
      'entities' => _drevops_project_entity_values($node, $field_name, $variables),
    };

    if ($values === []) {
      continue;
    }

    $rows[] = ['label' => (string) $info['label'], 'values' => $values];
  }

  return $rows;
}

/**
 * Resolves the plain text values held by a field.
 */
function _drevops_project_scalar_values(NodeInterface $node, string $field_name): array {
  $values = [];

  // Reading the raw values keeps one resolver serving both the string fields
  // and the integer year, which share no field-item type.
  foreach ($node->get($field_name)->getValue() as $item) {
    $text = trim((string) ($item['value'] ?? ''));

    if ($text === '') {
      continue;
    }

    $values[] = ['text' => $text];
  }

  return $values;
}

/**
 * Resolves the human-readable labels behind a list field's stored keys.
 */
function _drevops_project_list_values(NodeInterface $node, string $field_name): array {
  $field = $node->get($field_name);
  $allowed = options_allowed_values($field->getFieldDefinition()->getFieldStorageDefinition(), $node);
  $values = [];

  foreach ($field->getValue() as $item) {
    $key = (string) ($item['value'] ?? '');

    if ($key === '') {
      continue;
    }

    $values[] = ['text' => (string) ($allowed[$key] ?? $key)];
  }

  return $values;
}

/**
 * Resolves the links held by a link field.
 */
function _drevops_project_link_values(NodeInterface $node, string $field_name): array {
  $values = [];

  foreach ($node->get($field_name)->getValue() as $item) {
    $uri = trim((string) ($item['uri'] ?? ''));

    if ($uri === '') {
      continue;
    }

    $url = Url::fromUri($uri);
    $address = $url->toString();
    $text = trim((string) ($item['title'] ?? ''));

    $values[] = [
      'text' => $text !== '' ? $text : $address,
      'url' => $address,
      'is_external' => $url->isExternal(),
    ];
  }

  return $values;
}

/**
 * Resolves the entities referenced by a field as links to their own pages.
 */
function _drevops_project_entity_values(NodeInterface $node, string $field_name, array &$variables): array {
  $values = [];

  // The CivicTheme helper drops entities the reader cannot view and records
  // each one as a cacheable dependency, so a renamed entity cannot serve a
  // stale label from the node's render cache.
  foreach (civictheme_get_field_referenced_entities($node, $field_name, $variables) as $entity) {
    if (!$entity instanceof EntityInterface) {
      continue;
    }

    $values[] = [
      'text' => (string) $entity->label(),
      'url' => $entity->toUrl()->toString(),
    ];
  }

  return $values;
}
