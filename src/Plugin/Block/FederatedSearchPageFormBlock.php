<?php
namespace Drupal\search_api_federated_solr\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a "Federated Search Page Form" block.
 *
 * @Block(
 *   id = "federated_search_page_form_block",
 *   admin_label = @Translation("Federated Search Page Form block"),
 *   category = @Translation("Federated Search"),
 * )
 */
class FederatedSearchPageFormBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['container']['form'] = \Drupal::formBuilder()->getForm('Drupal\search_api_federated_solr\Form\FederatedSearchPageBlockForm');

    return $build;
  }
}
