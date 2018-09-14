<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "Urls" property.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Urls
 */
class UrlsProperty extends ConfigurablePropertyBase {

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
      '#title' => $this->t('URLs'),
      '#description' => $this->t('URLs pointing to this node on all sites containing.'),
    ];

    return $form;
  }

}
