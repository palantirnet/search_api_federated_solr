<?php
namespace Drupal\search_api_federated_solr\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 *  Add wrapper "docs" around results like Solr response object.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "solr_serializer",
 *   title = @Translation("Solr Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class SolrSerializer extends Serializer implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();
    // Wrap the Solr response object around the view results.
    $render = '{
      "response": {
        "docs":' . $render .
      '}
    }';

    return $render;
  }

}
