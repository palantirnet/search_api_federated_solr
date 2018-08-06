<?php

namespace Drupal\search_api_fields\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_fields\Plugin\search_api\processor\Property\MappedTermsProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Normalize multiple taxonomy terms into mapped terms.
 *
 * @see \Drupal\search_api_fields\Plugin\search_api\processor\Property\MappedTermsProperty
 *
 * @SearchApiProcessor(
 *   id = "mapped_terms",
 *   label = @Translation("Mapped terms"),
 *   description = @Translation("Normalize multiple taxonomy terms into mapped terms."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class MappedTerms extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Mapped terms'),
        'description' => $this->t('Normalize multiple taxonomy terms into mapped terms.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['mapped_terms'] = new MappedTermsProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the mapped terms fields on our index (there should only be one).
    $mapped_terms = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'mapped_terms');

    // Get the entity object for the item being indexed, exit if there's somehow not one.
    $entity = $item->getOriginalObject()->getValue();
    if (!$entity) {
      return;
    }

    // Define our array of mapped terms destination values.
    $mapped_terms_destination_values = [];

    // Set some helper vars for the entity and bundle type.
    $entity_type = $entity->getEntityTypeId();
    $bundle_type = $entity->bundle();

    // Get the bundle's fields.
    $entityManager = \Drupal::service('entity_field.manager');
    $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle_type);

    // Define array of potential taxonomy fields.
    $bundle_taxonomy_fields = [];

    // Process and set values for each mapped field on the index.
    foreach ($mapped_terms as $mapped_term) {

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
          return;
        }

        // Iterate through this item's taxonomy terms to find mapped_terms values.
        foreach ($entity_terms as $term) {
          // Load the taxonomy term entity.
          $term_entity = Term::load($term['target_id']);
          // Get the term's field definitions.
          $field_definitions = $term_entity->getFieldDefinitions();
          $mapped_term_definitions = array_filter($field_definitions, function ($field_definition) {
            return $field_definition->getType() === "mapped_terms";
          });

          // Since we don't know the field name which was added, we need to identify it by the field type.
          $mapped_term_field_names = array_map(function ($mapped_term_definitions) {
            return $mapped_term_definitions->getName();
          }, $mapped_term_definitions);

          // Iterate through any mapped_terms fields and get their values.
          foreach ($mapped_term_field_names as $field_name) {
            $mapped_term_values = $term_entity->$field_name->getValue();

            // If the mapped_terms field is populated, add its values to the destination_terms array.
            if (!empty($mapped_term_values)) {
              foreach ($mapped_term_values as $mapped_term_value) {
                $mapped_terms_destination_values[] = $mapped_term_value['value'];
              };
            }
          }
        };
      }

      // Remove any duplicate mapped_term_destination_values.
      $mapped_terms_destination_values = array_unique($mapped_terms_destination_values);

      // If the value does not already exist for this item, then add it.
      foreach ($mapped_terms_destination_values as $value) {
        $existing_values = $mapped_term->getValues();
        if (!array_search($value, $existing_values)) {
          $mapped_term->addValue($value);
        }
      }
    }
  }
}
