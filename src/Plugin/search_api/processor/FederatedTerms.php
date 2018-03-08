<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedTermProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Normalize multiple content types into a single federated field.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedTermProperty
 *
 * @SearchApiProcessor(
 *   id = "federated_term",
 *   label = @Translation("Federated terms"),
 *   description = @Translation("Normalize multiple taxonomy terms into a single federated term."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class FederatedTerms extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Federated terms'),
        'description' => $this->t('Normalize multiple taxonomy terms into a single federated term.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['federated_term'] = new FederatedTermProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the federated fields on our item.
    $federated_terms = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'federated_term');

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
    foreach ($federated_terms as $federated_term) {

      // Get configuration for the field.
      $configuration = $federated_term->getConfiguration();

      // If there's a config item for the entity and bundle type we're in, set the value for the field.
      if(!empty($configuration['field_data'][$entity_type][$bundle_type])) {
        // get the $entity taxonomy terms
        // check if they map to any of the source terms from the federated_term config sources
        // if so, set the value to the mapped value for the matched source term

        // Do not use setValues(), since that doesn't preprocess the values according to their data type.
        $federated_term->addValue($value);

      }
    }
  }
}
