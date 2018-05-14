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

    $form['#action'] = $this->getUrlGenerator()->generateFromRoute('search_api_federated_solr.search');
    $form['#method'] = 'get';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
  }

}
