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
      '#markup' => '<div id="root"><noscript>This search page requires Javascript in order to function.  <a href="https://www.whatismybrowser.com/guides/how-to-enable-javascript/auto">Learn how to enable Javascript in your browser.</a></noscript><p class="element-invisible" aria-hidden="true">Federated Solr Search App: If you see this message in your DevTools, it likely means there is an issue adding the app javascript library to this page.  Follow the steps in the search_api_federated_solr module README.</p></div>',
      '#attached' => array(
        'library' => array(
          'search_api_federated_solr/search',
        ),
      ),
    );
    return $element;
  }

}
