<?php

class SearchApiFederatedSolrTerms extends SearchApiAbstractAlterCallback {

  /**
   * @var SearchApiIndex
   */
  protected $index;

  /**
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    return array(
      'federated_terms' => array(
        'label' => t('Federated Term'),
        'description' => t('By adding this field to your search index configuration, you have enabled the federated terms processor to run when new items are indexed.  Next, add a "Federated Terms" field to any taxonomy vocabulary whose terms should be mapped to a "federated" term (this helps map terms across vocabularies and sites to a single "federated" term).  Then, edit terms in those vocabularies to add the federated term destination value (i.e. "Conditions>Blood Disorders").  Once that tagged content gets indexed, it will have "federated_terms" populated with any matching federated term destination values.'),
        'type' => 'list<string>',
        'cardinality' => -1,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    // if (empty($this->options['fields'])) { return; }

    $entity_type = $this->index->getEntityType();
    $entity_info = entity_get_info($entity_type);

    foreach ($items as &$item) {

      $id = entity_id($entity_type, $item);

      // Get the entity object for the item being indexed, exit if there's somehow not one.
      $entity = current(entity_load($entity_type, [$id]));
      if (!$entity) {
        return;
      }

      // Define our array of federated terms destination values.
      $federated_terms_destination_values = [];

      // Set some helper vars for the entity and bundle type.
      $bundle = $entity->{$entity_info['entity keys']['bundle']};

      // Get the bundle's fields.
      $bundle_fields = field_info_instances($entity_type, $bundle);

      // Define array of potential taxonomy fields.
      $bundle_taxonomy_fields = [];

      // Determine if / which taxonomy fields exist on the entity.
      foreach ($bundle_fields as $bundle_field) {
        $bundle_field_info = field_info_field($bundle_field['field_name']);
        if ($bundle_field_info['type'] === "entityreference") {
          if ($bundle_field_info['settings']['target_type'] == 'taxonomy_term') {
            $bundle_taxonomy_fields[$bundle_field['field_name']] = $bundle_field['label'];
          }
        }
      }

      // For each taxonomy field on the entity, get the terms.
      foreach ($bundle_taxonomy_fields as $taxonomy_field_id => $taxonomy_field_name) {

        // Iterate through each of the referenced terms.
        $lang = $entity->language;
        if (isset($entity->$taxonomy_field_id[$lang])) {
          foreach ($entity->$taxonomy_field_id[$lang] as $term_id) {
            $entity_term = taxonomy_term_load($term_id['target_id']);
            $entity_term_fields = field_info_instances('taxonomy_term', $entity_term->vocabulary_machine_name);

            // Iterate through each of the referenced term's fields.
            foreach ($entity_term_fields as $entity_term_field) {
              $entity_term_field_name = $entity_term_field['field_name'];
              $entity_term_field_info = field_info_field($entity_term_field_name);

              // Check if the term has a federated_terms field.
              if ($entity_term_field_info['type'] === "federated_terms") {
                $entity_term_federated_term = $entity_term->$entity_term_field_name;
                if (!empty($entity_term_federated_term)) {
                  foreach ($entity_term_federated_term['und'] as $federated_term) {
                    // Add the federated_terms field's value to index.
                    $federated_terms_destination_values[] = $federated_term['value'];
                  }
                }
              }
            }
          }
        }
      }

      // If there are federated_terms_destination_values save them to the index.
      if (!empty($federated_terms_destination_values)) {
        $item->federated_terms = $federated_terms_destination_values;
      }
    }
  }
}
