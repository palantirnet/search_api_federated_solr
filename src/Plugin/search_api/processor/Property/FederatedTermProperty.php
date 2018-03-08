<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
      '#description' => $this->t('Define the data to be sent to the index for each bundle taxononmy reference fields in the data sources set in your index configuration. Use static values or choose tokens using the picker below.'),
    ];

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $bundles = $datasource->getBundles();
      $entity_type = $datasource->getEntityTypeId();

      // Make an array of all the entity types we're working with to pass to token_help.
      $entity_types[] = $entity_type;

      foreach ($bundles as $bundle_id => $bundle_label) {

        $entityManager = \Drupal::service('entity_field.manager');
        $bundle_fields = $entityManager->getFieldDefinitions($entity_type, $bundle_id);
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
          $form[$entity_type][$bundle_id] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Taxonomy terms data for %datasource » %bundle', ['%datasource' => $datasource->label(), '%bundle' => $bundle_label]),
          ];

          // Create a config select field for each bundle with at least 1 taxonomy term entity reference field.
          $form[$entity_type][$bundle_id]['taxonomy_fields'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id,
            '#type' => 'select',
            '#title' => $this->t('Taxonomy term reference fields'),
            '#description' => $this->t('Select a field to begin assigning term values'),
            '#element_validate' => array('token_element_validate'),
            '#token_types' => array($entity_type),
            '#options' => $bundle_field_names,
          ];

          // Create a fieldset for the category terms.
          $form[$entity_type][$bundle_id]['categories'] = [
            '#type' => 'details',
            '#title' => $this->t('Categories terms for %bundle', ['%bundle' => $bundle_label]),
            '#fieldset' => $entity_type . '_' . $bundle_id,
            '#open' => TRUE,
          ];

          // Create a categories taxonomy term entity reference autocomplete tag widget.
          $form[$entity_type][$bundle_id]['categories']['term_ref_field'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id .'_categories',
            '#type' => 'entity_autocomplete',
            '#target_type' => 'taxonomy_term',
            '#title' => $this->t('Source categories terms'),
            '#description' => $this->t('Start typing some category terms.  You can separate multiple terms with a comma.'),
            '#default_value' => array(),
            '#tags' => TRUE,
            '#selection_settings' => array(
              'target_bundles' => array('categories'),
            ),
          ];

          // Create a config text field for the categories terms mapped value.
          $form[$entity_type][$bundle_id]['categories']['mapped_value'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id .'_categories',
            '#type' => 'textfield',
            '#title' => $this->t(' » Destination categories term'),
            '#description' => $this->t('The value to which the corresponding terms should map.'),
            '#element_validate' => array('token_element_validate'),
            '#token_types' => array($entity_type),
          ];

          // Create a fieldset for the topic terms.
          $form[$entity_type][$bundle_id]['topic'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Topic terms for %bundle', ['%bundle' => $bundle_label]),
            '#fieldset' => $entity_type . '_' . $bundle_id,
          ];

          // Create a topic taxonomy term entity reference autocomplete tag widget.
          $form[$entity_type][$bundle_id]['topic']['term_ref_field'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id . '_topic',
            '#type' => 'entity_autocomplete',
            '#target_type' => 'taxonomy_term',
            '#title' => $this->t('Source topic terms'),
            '#description' => $this->t('Start typing some topic terms.  You can separate multiple terms with a comma.'),
            '#default_value' => array(),
            '#tags' => TRUE,
            '#selection_settings' => array(
              'target_bundles' => array('topic'),
            ),
          ];

          // Create a config text field for the topic terms mapped value.
          $form[$entity_type][$bundle_id]['topic']['mapped_value'] = [
            '#fieldset' => $entity_type . '_' . $bundle_id . '_topic',
            '#type' => 'textfield',
            '#title' => $this->t(' » Destination topic term'),
            '#description' => $this->t('The value to which the corresponding terms should map.'),
            '#element_validate' => array('token_element_validate'),
            '#token_types' => array($entity_type),
          ];
        }

        // Set the default value if something already exists in our config.
//        if (isset($configuration[$entity_type][$bundle_id]['field_data'])) {
//          $form[$entity_type][$bundle_id]['field_data']['#default_value'] = $configuration[$entity_type][$bundle_id]['field_data'];
//        }
      }
    }

    // Build the token picker.
    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $entity_types,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'field_data' => array_filter($form_state->getValue('field_data')),
    ];

    $field->setConfiguration($values);
  }

}
