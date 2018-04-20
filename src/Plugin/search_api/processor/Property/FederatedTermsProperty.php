<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines an "federated terms" property.
 *
 * @see \Drupal\search_api_federated_solr\Plugin\search_api\processor\FederatedTerms
 */
class FederatedTermsProperty extends ConfigurablePropertyBase {

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

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['field_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Federated terms'),
      '#description' => $this->t('By adding this field to your search index configuration, you have enabled the federated terms processor to run when new items are indexed.  Next, add a "Federated Terms" field to any taxonomy vocabulary whose terms should be mapped to a "federated" term (this helps map terms across vocabularies and sites to a single "federated" term).  Then, edit terms in those vocabularies to add the federated term destination value (i.e. "Conditions>Blood Disorders").  Once that tagged content gets indexed, it will have "federated_terms" populated with any matching federated term destination values.'),
    ];

    return $form;
  }
}
