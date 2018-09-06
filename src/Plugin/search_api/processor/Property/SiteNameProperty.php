<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "site name" property.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\SiteName
 */
class SiteNameProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'site_name' => \Drupal::config('system.site')->get('name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name'),
      '#description' => $this->t('The name of the site from which this content originated. This can be useful if indexing multiple sites with a single search index.'),
      '#default_value' => $configuration['site_name'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'site_name' => $form_state->getValue('site_name'),
    ];
    $field->setConfiguration($values);
  }

}
