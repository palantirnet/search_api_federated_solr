<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedTermsProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Normalize multiple taxonomy terms into federated terms.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\FederatedTermsProperty
 *
 * @SearchApiProcessor(
 *   id = "federated_terms",
 *   label = @Translation("Federated terms"),
 *   description = @Translation("Normalize multiple taxonomy terms into federated terms."),
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
        'description' => $this->t('Normalize multiple taxonomy terms into federated terms.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['federated_terms'] = new FederatedTermsProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /* Get all of the federated terms fields on our index
     * (there should only be one).
     */
    $federated_terms = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'federated_terms');

    // Get the entity object for the item being indexed.
    $entity = $item->getOriginalObject()->getValue();
    // Exit if there's somehow not one.
    if (!$entity) {
      return;
    }

    // Define our array of federated terms destination values.
    $federated_terms_destination_values = [];

    // Set some helper vars for the entity and bundle type.
    $entity_type = $entity->getEntityTypeId();
    $bundle_type = $entity->bundle();

    // Get the bundle's fields.
    $entityManager = \Drupal::service('entity_field.manager');
    $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle_type);

    // Define array of potential taxonomy fields.
    $bundle_taxonomy_fields = [];

    // Process and set values for each federated field on the index.
    foreach ($federated_terms as $federated_term) {

      // Determine if / which taxonomy fields exist on the entity.
      foreach ($bundle_fields as $bundle_field) {
        $bundle_field_type = $bundle_field->getType();
        if ($bundle_field_type === "entity_reference") {
          $bundle_field_settings = $bundle_field->getSettings();
          if ($bundle_field_settings['target_type'] == 'taxonomy_term') {
            $bundle_taxonomy_fields[$bundle_field->getName()] = $bundle_field->getLabel();
          }
        }
      }

      // For each taxonomy field on the entity, get the terms.
      foreach ($bundle_taxonomy_fields as $taxonomy_field_id => $taxonomy_field_name) {
        // Get the entity's term data for that taxonomy field.
        $entity_terms = $entity->$taxonomy_field_id->getValue();

        // If there are no taxonomy terms on this $entity, do nothing.
        if (empty($entity_terms)) {
          continue;
        }

        // Iterate through this item's terms to find federated_terms values.
        foreach ($entity_terms as $term) {
          // Load the taxonomy term entity.
          $term_entity = Term::load($term['target_id']);
          // Get the term's field definitions.
          $field_definitions = $term_entity->getFieldDefinitions();
          $federated_term_definitions = array_filter($field_definitions, function ($field_definition) {
            return $field_definition->getType() === "federated_terms";
          });

          /* Since we don't know the field name which was added,
           * we need to identify it by the field type.
           */
          $federated_term_field_names = array_map(function ($federated_term_definition) {
            return $federated_term_definition->getName();
          }, $federated_term_definitions);

          // Iterate through any federated_terms fields and get their values.
          foreach ($federated_term_field_names as $field_name) {
            $federated_term_values = $term_entity->$field_name->getValue();

            // If the federated_terms field is populated.
            if (!empty($federated_term_values)) {
              foreach ($federated_term_values as $federated_term_value) {
                // Add its values to the destination_terms array.
                $federated_terms_destination_values[] = $federated_term_value['value'];
              };
            }
          }
        };
      }

      // Remove any duplicate federated_term_destination_values.
      $federated_terms_destination_values = array_unique($federated_terms_destination_values);

      // If the value does not already exist for this item, then add it.
      foreach ($federated_terms_destination_values as $value) {
        $existing_values = $federated_term->getValues();
        if (!array_search($value, $existing_values)) {
          $federated_term->addValue($value);
        }
      }
    }
  }

}
