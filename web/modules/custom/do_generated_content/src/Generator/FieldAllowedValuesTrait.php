<?php

declare(strict_types=1);

namespace Drupal\do_generated_content\Generator;

use Drupal\field\FieldConfigInterface;

/**
 * Reads the allowed values of a list field from its storage definition.
 *
 * Hardcoding the values would stop covering a field the moment CivicTheme adds
 * one, and the gap would be invisible because the site still looks populated.
 *
 * @codeCoverageIgnore
 */
trait FieldAllowedValuesTrait {

  /**
   * Allowed values keyed by field config id.
   *
   * @var array<string, string[]>
   */
  protected array $allowedValuesCache = [];

  /**
   * Get the allowed values of a list field.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @return string[]
   *   Allowed values, in the order the field declares them.
   */
  protected function allowedValues(string $entity_type, string $bundle, string $field_name): array {
    $id = $entity_type . '.' . $bundle . '.' . $field_name;

    if (isset($this->allowedValuesCache[$id])) {
      return $this->allowedValuesCache[$id];
    }

    $field = $this->entityTypeManager->getStorage('field_config')->load($id);

    if (!$field instanceof FieldConfigInterface) {
      throw new \RuntimeException(sprintf('Field %s does not exist.', $id));
    }

    $allowed_values = $field->getFieldStorageDefinition()->getSetting('allowed_values');

    $this->allowedValuesCache[$id] = array_keys(is_array($allowed_values) ? $allowed_values : []);

    return $this->allowedValuesCache[$id];
  }

}
