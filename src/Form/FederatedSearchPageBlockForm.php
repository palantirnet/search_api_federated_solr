<?php

/**
 * @file
 * Contains \Drupal\search_api_solr_federated\Form\FederatedSearchPageBlockForm.
 */

namespace Drupal\search_api_federated_solr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FederatedSearchPageForm.
 *
 * @package Drupal\search_api_federated_solr\Form
 */
class FederatedSearchPageBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'federated_search_page_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $renderer = \Drupal::service('renderer');
    $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');


    $form['search'] = array(
      '#type' => 'search',
      '#name' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => '',
      '#attributes' => array(
        'title' => $this->t('Enter the terms you wish to search for.'),
        'placeholder' => 'Search',
      ),
      '#prefix' => '<div class="container-inline">',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#name' => '',
      '#suffix' => '</div>',
    );


    // Send site name as qs param if app is configured to load w/default site.
    if ($app_config->get('facet.site_name.set_default')) {
      $search_index = $app_config->get('index.id');
      $index_config = \Drupal::config('search_api.index.' . $search_index);
      $site_name = $index_config->get('field_settings.site_name.configuration.site_name');

      $form['ss_site_name'] = [
        '#type' => 'hidden',
        '#name' => 'ss_site_name',
        '#default_value' => $site_name
      ];

      // Ensure that this form's render cache is invalidated when search app
      // config is updated.
      $renderer->addCacheableDependency($form, $index_config);

    }

    $form['#action'] = $this->getUrlGenerator()->generateFromRoute('search_api_federated_solr.search');

    $form['#method'] = 'get';

    // Ensure that this form's render cache is invalidated when search app
    // config is updated.
    $renderer->addCacheableDependency($form, $app_config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
  }

}
