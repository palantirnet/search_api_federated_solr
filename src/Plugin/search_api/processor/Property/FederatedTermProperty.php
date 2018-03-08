<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Defines an "federated term" property.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\FederatedTerms
 */
class FederatedTermProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => 'union',
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $index = $field->getIndex();
    $configuration = $field->getConfiguration();

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['field_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Federated terms'),
      '#description' => $this->t('Define the data to be sent to the index for each bundle taxonomy reference fields in the data sources set in your index configuration.'),
    ];

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $bundles     = $datasource->getBundles();
      $entity_type = $datasource->getEntityTypeId();

      foreach ($bundles as $bundle_id => $bundle_label) {
        $entityManager      = \Drupal::service('entity_field.manager');
        $bundle_fields      = $entityManager->getFieldDefinitions($entity_type, $bundle_id);
        $bundle_field_names = [];

        // Only add entity reference fields with a target type of taxonomy term.
        foreach ($bundle_fields as $bundle_field) {
          $bundle_field_type = $bundle_field->getType();
          if ($bundle_field_type === "entity_reference") {
            $bundle_field_settings = $bundle_field->getSettings();
            if ($bundle_field_settings['target_type'] == 'taxonomy_term') {
              $bundle_field_names[$bundle_field->getName()] = $bundle_field->getLabel();
            }
          }
        }

        // Render mapping fields if there are taxonomy term fields.
        if (!empty($bundle_field_names)) {
          // Create a fieldset per bundle with taxonomy term reference fields.
          $form['field_data'][$entity_type][$bundle_id] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Taxonomy terms data for %datasource » %bundle', [
              '%datasource' => $datasource->label(),
              '%bundle' => $bundle_label
            ]),
          ];

          // Define a default option for the select.
          $bundle_field_names['default'] = 'Select a taxonomy term field';
          // Determine what the selected value should be: either the default or load a saved value.
          $default = 'default';
          // Set the default value if something already exists in our config.
          if (isset($configuration['field_data'][$entity_type][$bundle_id]['taxonomy_field'])) {
            $default = $configuration['field_data'][$entity_type][$bundle_id]['taxonomy_field'];
          }

          // Create a config select field for each bundle with at least 1 taxonomy term entity reference field.
          $form['field_data'][$entity_type][$bundle_id]['taxonomy_field'] = [
            '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id,
            '#type' => 'select',
            '#title' => $this->t('Taxonomy term reference field'),
            '#description' => $this->t('Select a field to begin assigning term values'),
            '#options' => $bundle_field_names,
            '#default_value' => $default,
          ];

          // Create a fieldset for the category terms.
          $form['field_data'][$entity_type][$bundle_id]['field_categories'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Categories terms for %bundle', ['%bundle' => $bundle_label]),
            '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id,
            '#prefix' => '<div id="categories-row-wrapper">',
            '#suffix' => '</div>',
          ];

          // Create a categories taxonomy term entity reference autocomplete tag widget.
          $form['field_data'][$entity_type][$bundle_id]['field_categories']['source_terms'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id . '_field_categories',
            '#type' => 'entity_autocomplete',
            '#target_type' => 'taxonomy_term',
            '#title' => $this->t('Source categories terms'),
            '#description' => $this->t('Start typing some category terms.  You can separate multiple terms with a comma.'),
            '#default_value' => [],
            '#tags' => TRUE,
            '#selection_settings' => [
              'target_bundles' => ['categories'],
            ],
          ];

          // Set the default value if something already exists in our config.
          if (isset($configuration['field_data'][$entity_type][$bundle_id]['field_categories']['source_terms'])) {
            // Get the ids from the saved config value.
            $target_ids = array_map(function ($item) {
              return $item['target_id'];
            }, $configuration['field_data'][$entity_type][$bundle_id]['field_categories']['source_terms']);
            // Load the term entity from the id.
            $term_entities = array_map(function ($tid) {
              return Term::load($tid);
            }, $target_ids);

            // The default value of an entity reference autocomplete field must be an entity object or array of entity objects.
            $form['field_data'][$entity_type][$bundle_id]['field_categories']['source_terms']['#default_value'] = count($term_entities) > 1 ? $term_entities : $term_entities[0];
          }

          // Create a config text field for the categories terms mapped value.
          $form['field_data'][$entity_type][$bundle_id]['field_categories']['destination_term'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id . '_field_categories',
            '#type' => 'textfield',
            '#title' => $this->t(' » Destination categories term'),
            '#description' => $this->t('The value to which the corresponding terms should map.'),
          ];

          // Set the default value if something already exists in our config.
          if (isset($configuration['field_data'][$entity_type][$bundle_id]['field_categories']['destination_term'])) {
            $form['field_data'][$entity_type][$bundle_id]['field_categories']['destination_term']['#default_value'] = $configuration['field_data'][$entity_type][$bundle_id]['field_categories']['destination_term'];
          }

          // Create a fieldset for the topic terms.
          $form['field_data'][$entity_type][$bundle_id]['field_topics'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Topics terms for %bundle', ['%bundle' => $bundle_label]),
            '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id,
          ];

          // Create a topic taxonomy term entity reference autocomplete tag widget.
          $form['field_data'][$entity_type][$bundle_id]['field_topics']['source_terms'] = [
            '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id . '_field_topics',
            '#type' => 'entity_autocomplete',
            '#target_type' => 'taxonomy_term',
            '#title' => $this->t('Source topics terms'),
            '#description' => $this->t('Start typing some topic terms.  You can separate multiple terms with a comma.'),
            '#default_value' => [],
            '#tags' => TRUE,
            '#selection_settings' => [
              'target_bundles' => ['topics'],
            ],
          ];

          // Set the default value if something already exists in our config.
          if (isset($configuration['field_data'][$entity_type][$bundle_id]['field_topics']['source_terms'])) {
            // Get the ids from the saved config value.
            $target_ids = array_map(function ($item) {
              return $item['target_id'];
            }, $configuration['field_data'][$entity_type][$bundle_id]['field_topics']['source_terms']);
            // Load the term entity from the id.
            $term_entities = array_map(function ($tid) {
              return Term::load($tid);
            }, $target_ids);

            // The default value of an entity reference autocomplete field must be an entity object or array of entity objects.
            $form['field_data'][$entity_type][$bundle_id]['field_topics']['source_terms']['#default_value'] = count($term_entities) > 1 ? $term_entities : $term_entities[0];
          }

          // Create a config text field for the topic terms mapped value.
          $form['field_data'][$entity_type][$bundle_id]['field_topics']['destination_term'] = [
            '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id . '_field_topics',
            '#type' => 'textfield',
            '#title' => $this->t(' » Destination topics term'),
            '#description' => $this->t('The value to which the corresponding terms should map. This value will render as search filter label exactly as it appears here (i.e. with the same capitalization and punctuation).'),
          ];

          // Set the default value if something already exists in our config.
          if (isset($configuration['field_data'][$entity_type][$bundle_id]['field_topics']['destination_term'])) {
            $form['field_data'][$entity_type][$bundle_id]['field_topics']['destination_term']['#default_value'] = $configuration['field_data'][$entity_type][$bundle_id]['field_topics']['destination_term'];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'field_data' => array_filter($form_state->getValue('field_data')),
    ];

    // filter out the array items where the default taxonomy term field value is still present

    $field->setConfiguration($values);
  }

}
