<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\search_api\Processor\ConfigurablePropertyInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines an "federated field" property.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\FederatedFields
 */
class FederatedFieldProperty extends ConfigurablePropertyBase {

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
      '#title' => $this->t('Federated data'),
      '#description' => $this->t('Set the data to be sent to the index for each bundle in the data sources set in your index configuration. Use static values or choose tokens using the picker below.'),
    ];

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $bundles = $datasource->getBundles();
      $entity_type = $datasource->getEntityTypeId();

      // Make an array of all the entity types we're working with to pass to token_help.
      $entity_types[] = $entity_type;

      foreach ($bundles as $bundle_id => $bundle_label) {

        // Create a config field for each bundle in our enabled datasources.
        $form['field_data'][$entity_type][$bundle_id] = [
          '#type' => 'textfield',
          '#title' => $this->t('Field data for %datasource » %bundle', ['%datasource' => $datasource->label(), '%bundle' => $bundle_label]),
          '#element_validate' => array('token_element_validate'),
          '#token_types' => array($entity_type),
        ];

        // Set the default value if something already exists in our config.
        if (isset($configuration['field_data'][$entity_type][$bundle_id])) {
          $form['field_data'][$entity_type][$bundle_id]['#default_value'] = $configuration['field_data'][$entity_type][$bundle_id];
        }
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
    \Drupal::logger('search_api_federated_solr')->notice('Submitted @values.',
      array(
        '@values' => print_r(array_filter($form_state->getValue('field_data')),TRUE),
      ));
    $field->setConfiguration($values);
  }



}
