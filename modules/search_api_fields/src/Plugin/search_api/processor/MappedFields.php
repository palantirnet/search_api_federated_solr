<?php

namespace Drupal\search_api_fields\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_fields\Plugin\search_api\processor\Property\MappedFieldProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Normalize multiple content types into a single mapped field.
 *
 * @see \Drupal\search_api_fields\Plugin\search_api\processor\Property\MappedFieldProperty
 *
 * @SearchApiProcessor(
 *   id = "mapped_field",
 *   label = @Translation("Mapped fields"),
 *   description = @Translation("Normalize multiple content types into a single mapped field."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class MappedFields extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Mapped field'),
        'description' => $this->t('Normalize multiple content types into a single mapped field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['mapped_field'] = new MappedFieldProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the mapped fields on our item.
    $mapped_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'mapped_field');

    // Get the entity object, bail if there's somehow not one.
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity) {
      // Apparently we were active for a wrong item.
      return;
    }

    // Set some helper vars for the entity and bundle type.
    $entity_type = $entity->getEntityTypeId();
    $bundle_type = $entity->bundle();

    // Process and set values for each mapped field on the item.
    foreach ($mapped_fields as $mapped_field) {

      // Get configuration for the field.
      $configuration = $mapped_field->getConfiguration();

      // If there's a config item for the entity and bundle type we're in, set the value for the field.
      if(!empty($configuration['field_data'][$entity_type][$bundle_type])) {
        $token = \Drupal::token();
        // If the token replacement produces a value, add to this item.
        if ($value = $token->replace($configuration['field_data'][$entity_type][$bundle_type], [$entity_type => $entity], ['clear' => true])) {
          // Do not use setValues(), since that doesn't preprocess the values according to their data type.
          $mapped_field->addValue($value);
        }

      }
    }
  }
}
