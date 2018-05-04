<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedFieldProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Normalize multiple content types into a single federated field.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedFieldProperty
 *
 * @SearchApiProcessor(
 *   id = "federated_field",
 *   label = @Translation("Federated fields"),
 *   description = @Translation("Normalize multiple content types into a single federated field."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class FederatedFields extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Federated field'),
        'description' => $this->t('Normalize multiple content types into a single federated field.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['federated_field'] = new FederatedFieldProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the federated fields on our item.
    $federated_fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'federated_field');

    // Get the entity object, bail if there's somehow not one.
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity) {
      // Apparently we were active for a wrong item.
      return;
    }

    // Set some helper vars for the entity and bundle type.
    $entity_type = $entity->getEntityTypeId();
    $bundle_type = $entity->bundle();

    // Process and set values for each federated field on the item.
    foreach ($federated_fields as $federated_field) {

      // Get configuration for the field.
      $configuration = $federated_field->getConfiguration();

      // If there's a config item for the entity and bundle type we're in, set the value for the field.
      if(!empty($configuration['field_data'][$entity_type][$bundle_type])) {
        $token = \Drupal::token();
        // If the token replacement produces a value, add to this item.
        if ($value = $token->replace($configuration['field_data'][$entity_type][$bundle_type], [$entity_type => $entity], ['clear' => true])) {
          // Do not use setValues(), since that doesn't preprocess the values according to their data type.
          $federated_field->addValue($value);
        }

      }
    }
  }
}
