<?php
namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the search results page.
 */
class SearchController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function searchPage() {
    $element = array(
      '#markup' => '<div id="root">Hello, world</div>',
      '#attached' => array(
        'library' => array(
          'search_api_federated_solr/search',
        ),
      ),
    );
    return $element;
  }

}
