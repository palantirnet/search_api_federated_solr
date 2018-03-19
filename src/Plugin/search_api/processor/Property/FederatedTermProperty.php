<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines a "federated term" property.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\FederatedTerm
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
      // Define a variable to track number of rows per bundle->taxonomy field.
      $num_rows = [];

      foreach ($bundles as $bundle_id => $bundle_label) {
        $entityManager      = \Drupal::service('entity_field.manager');
        $bundle_fields      = $entityManager->getFieldDefinitions($entity_type, $bundle_id);
        $bundle_taxonomy_field_names = [];

        // Build array of entity reference fields with a target type of taxonomy term.
        foreach ($bundle_fields as $bundle_field) {
          $bundle_field_type = $bundle_field->getType();
          if ($bundle_field_type === "entity_reference") {
            $bundle_field_settings = $bundle_field->getSettings();
            if ($bundle_field_settings['target_type'] == 'taxonomy_term') {
              $bundle_taxonomy_field_names[$bundle_field->getName()] = $bundle_field->getLabel();
            }
          }
        }

        // Create a fieldset per bundle if that bundle has a taxonomy term field.
        if (!empty($bundle_taxonomy_field_names)) {
          // Create a fieldset per bundle with taxonomy term reference fields.
          $form['field_data'][$entity_type][$bundle_id] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Taxonomy terms data for %datasource » %bundle', [
              '%datasource' => $datasource->label(),
              '%bundle' => $bundle_label
            ]),
          ];

          // Define a default option for the taxonomy term field select.
          $bundle_taxonomy_field_names['default'] = 'Select a taxonomy term field';
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
            '#options' => $bundle_taxonomy_field_names,
            '#default_value' => $default,
          ];

          // Remove the "default" taxonomy field name from array, to avoid defining a fieldset for it.
          unset($bundle_taxonomy_field_names['default']);

          // Define the rest of the form elements dynamically, per taxonomy_field, and only make  visible when the respective field is selected.
          foreach ($bundle_taxonomy_field_names as $bundle_taxonomy_field_id => $bundle_taxonomy_field_name) {

            // Get the number of fieldset rows per bundle taxonomy field values (maintained in form state).
            $num_rows[$bundle_id][$bundle_taxonomy_field_id] = $form_state->get($bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows');

            // Create a fieldset for the bundle's selected taxonomy field.
            $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id] = [
              '#type' => 'fieldset',
              '#title' => $this->t($bundle_taxonomy_field_name . ' terms for %bundle', ['%bundle' => $bundle_label]),
              '#fieldset' => 'field_data_' . $entity_type . '_' . $bundle_id,
              '#prefix' => '<div id="' . $entity_type . '-' . $bundle_id . '-' . $bundle_taxonomy_field_id . '-row-wrapper">',
              '#suffix' => '</div>',
              // Show this fieldset only when the respective field has been chosen from the taxonomy_field select.
              '#states' => [
                'visible' => [
                  'select[name="field_data[' . $entity_type . '][' .$bundle_id . '][taxonomy_field]"]' => [
                        'value' => $bundle_taxonomy_field_id
                      ],
                ],
              ],
            ];

            // Get the target bundle(s) for this field.
            $target_bundles = array_values($bundle_fields[$bundle_taxonomy_field_id]->getSettings()['handler_settings']['target_bundles']);

            // Render the number of rows based on form state (i.e. has the add one button been pressed?).
            if (is_null($num_rows[$bundle_id][$bundle_taxonomy_field_id])) {
              ${$bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows'} = $form_state->set($bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows', 1);
              $num_rows[$bundle_id][$bundle_taxonomy_field_id] = 1;
            }

            for ($i = 0; $i < $num_rows[$bundle_id][$bundle_taxonomy_field_id]; $i++) {
              // Create a taxonomy term entity reference autocomplete tag widget.
              $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms'] = [
                '#fieldset' => $entity_type . '_' . $bundle_id . '_' . $bundle_taxonomy_field_id,
                '#type' => 'entity_autocomplete',
                '#target_type' => 'taxonomy_term',
                '#title' => $this->t('Source ' . $bundle_taxonomy_field_name . ' terms'),
                '#description' => $this->t('Start typing some ' . $bundle_taxonomy_field_name . ' terms.  You can separate multiple terms with a comma.'),
                '#default_value' => [],
                '#tags' => TRUE,
                '#selection_settings' => [
                  'target_bundles' => $target_bundles,
                ],
              ];

              // Set the default value if something already exists in our config.
              if (isset($configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms'])) {
                // Get the ids from the saved config value.
                $target_ids = array_map(function ($item) {
                  return $item['target_id'];
                }, $configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms']);
                // Load the term entity from the id.
                $term_entities = array_map(function ($tid) {
                  return Term::load($tid);
                }, $target_ids);

                // The default value of an entity reference autocomplete field must be an entity object or array of entity objects.
                $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms']['#default_value'] = count($term_entities) > 1 ? $term_entities : $term_entities[0];
              }

              // FAILS: Test ajax functionality with text field vs ER field by
              // uncommenting the field below and commenting out the ER field
              // above.
//            $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms'] = [
//              '#fieldset' => $entity_type . '_' . $bundle_id . '_' . $bundle_taxonomy_field_id,
//              '#type' => 'textfield',
//              '#title' => $this->t('Source ' . $bundle_taxonomy_field_name . ' terms'),
//              '#description' => $this->t('Start typing some ' . $bundle_taxonomy_field_name . ' terms.  You can separate multiple terms with a comma.'),
//            ];
//
//            // Set the default value if something already exists in our config.
//            if (isset($configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms'])) {
//              $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms']['#default_value'] = $configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['source_terms'];
//            }

              // Create a config text field for the terms' destination value.
              $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['destination_term'] = [
                '#fieldset' => $entity_type . '_' . $bundle_id . '_' . $bundle_taxonomy_field_id,
                '#type' => 'textfield',
                '#title' => $this->t(' » Destination ' . $bundle_taxonomy_field_name . ' term'),
                '#description' => $this->t('The value to which the corresponding terms should map.'),
              ];

              // Set the default value if something already exists in our config.
              if (isset($configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['destination_term'])) {
                $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['destination_term']['#default_value'] = $configuration['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id][$i]['destination_term'];
              }
            }

            // Create an "add another" button with an ajax callback.
            $form['field_data'][$entity_type][$bundle_id][$bundle_taxonomy_field_id]['add_one'] = [
              '#name' => $entity_type . '_' . $bundle_id . $bundle_taxonomy_field_id . '_add_one',
              '#type' => 'submit',
              '#value' => $this->t('Add another'),
              '#submit' => [[get_class($this), 'addOne']],
              '#ajax' => [
                'callback' => [get_class($this), 'addMoreCallback'],
                'wrapper' => $entity_type . '-' . $bundle_id . '-' . $bundle_taxonomy_field_id . '-row-wrapper',
              ],
            ];
          }
        }
      }
    }

    return $form;
  }

  /**
   * Submit handler for the "add one" button.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    $bundle_id = $triggered_element['#parents'][2];
    $bundle_taxonomy_field_id = $triggered_element['#parents'][3];

    ${$bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows'} = $form_state->get($bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows');
    $add_button = ${$bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows'} + 1;

    $form_state->set($bundle_id . '_' . $bundle_taxonomy_field_id . '_num_rows', $add_button);
    if(true){}; // TO TEST: set breakpoint here.
    $form_state->setRebuild();
  }

  /**
   * Callback for ajax-enabled "add more" button.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   *   The bundle's taxonomy field fieldset with an extra empty row.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    $triggered_element = $form_state->getTriggeringElement();
    $entity_id = $triggered_element['#parents'][1];
    $bundle_id = $triggered_element['#parents'][2];
    $bundle_taxonomy_field_id = $triggered_element['#parents'][3];
    if(true){}; // TO TEST:  set breakpoint here.
    return $form['field_data'][$entity_id][$bundle_id][$bundle_taxonomy_field_id];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    // Get all of the submitted field data.
    $field_data =  array_filter($form_state->getValue('field_data'));

    $non_empty_values = [];
    // Filter out the data which still has the default value for the taxonomy field select.
    // ['field_data'][$entity_id][$bundle_id][$bundle_taxonomy_field_id][$i].
    //  ^ map         ^ filter    ^ foreach   ^ foreach + logic + unset  ^ logic + unset.
    $non_empty_values['field_data'] = array_map(function(&$entity_id) {
      return array_filter($entity_id, function(&$bundle_id) {
        // Iterate over each taxonomy field's fieldset.
        foreach($bundle_id as $key=>&$field) {
          // Iterate over each fieldset's rows (each will have at least 1).
          // @todo do not iterate over $field when == "default"
          foreach($field as $row=>$value) {
            // Unset any child elements with no taxonomy field target_ids.
            if (array_key_exists('source_terms', $value) && is_null($value['source_terms'])) {
              unset($field[$row]);
            }
            // Remove the add_one button.
            unset($field['add_one']);
          }
          // Remove any empty taxonomy field names.
          if (empty($field)) {
            unset($bundle_id[$key]);
          }
        }
        // Remove array elements which have not had taxonomy field selected.
        return $bundle_id['taxonomy_field'] !== 'default';
      });
    }, $field_data);

    // Submit only the data for the populated fields.
    $field->setConfiguration($non_empty_values);
  }
}
