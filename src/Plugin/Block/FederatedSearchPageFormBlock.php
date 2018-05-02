<?php
namespace Drupal\search_api_federated_solr\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Federated Search Page Form" block.
 *
 * @Block(
 *   id = "federated_search_form_block",
 *   admin_label = @Translation("Federated Search Page Form block"),
 *   category = @Translation("Federated Search"),
 * )
 */
class FederatedSearchPageFormBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['federated_search_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Page URL'),
      '#description' => $this->t('What is the URL of the federated search page we should redirect to?'),
      '#default_value' => isset($config['federated_search_page_url']) ? $config['federated_search_page_url'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['federated_search_page_url'] = $values['federated_search_page_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['federated_search_page_url'])) {
      $url = $config['federated_search_page_url'];
    }
    else {
      $url = $this->t('/search-app');
    }

    return array(
      '#markup' => $this->t('Hello @url!', array(
        '@url' => $url,
      )),
      '#theme' => 'search_api_federated_solr',
      '#federated_search_page_url' => $this->configuration['federated_search_page_url'],
    );
  }

}
