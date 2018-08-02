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
    $renderer = \Drupal::service('renderer');

    $config = \Drupal::configFactory()->getEditable('search_api_federated_solr.search_app.settings');
    $index_config = \Drupal::config('search_api.index.' . $config->get('index.id'));

    // Define the federated search app configuration array.
    // The twig template will json_encode this array into the object expected by
    // the search app: https://github.com/palantirnet/federated-search-react/blob/master/src/.env.local.js.example

    $federated_search_app_config = [];

    // REQUIRED: The default solr backend.
    $federated_search_app_config['url'] = $config->get('index.server_url');

    // OPTIONAL: The username and password for Basic Authentication on the server.
    // The username and password will be combined and base64 encoded as per the application.
    $federated_search_app_config['userpass'] = base64_encode($config->get('index.username') . ':' . $config->get('index.password'));

    // Validate that there is still a site name property set for this index.
    $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
    $config->set('index.has_site_name_property', $site_name_property ? true : false);

    // Determine if config option to set default site name is set.
    $set_default_site = $config->get('facet.site_name.set_default');

    // If we no longer have a site name property so unset the set default config.
    if ($set_default_site && !$site_name_property) {
      $config->set('facet.site_name.set_default', 0);
    }
    // See Drupal\search_api_federated_solr\Form\FederatedSearchPageForm class
    // The default "Site Name" facet value is passed by search form in initial get request.

    // OPTIONAL: The text to display when the app loads with no search term.
    if ($search_prompt = $config->get('content.search_prompt')) {
      $federated_search_app_config['searchPrompt'] = $search_prompt;
    }

    // OPTIONAL: The text to display when a search returns no results.
    if ($no_results = $config->get('content.no_results')) {
      $federated_search_app_config['noResults'] = $no_results;
    }

    // OPTIONAL: The number of search results to show per page.
    if ($rows = $config->get('results.rows')) {
      $federated_search_app_config['rows'] = intval($rows);
    }

    // OPTIONAL: The number of page buttons to show for pagination.
    if ($pagination_buttons = $config->get('pagination.buttons')) {
      $federated_search_app_config['paginationButtons'] = intval($pagination_buttons);
    }

    // OPTIONAL: The number of page buttons to show for pagination.
    if ($page_title = $config->get('page_title')) {
      $federated_search_app_config['pageTitle'] = $page_title;
    }


    $element = [
      '#theme' => 'search_app',
      '#federated_search_app_config' => $federated_search_app_config,
      '#attached' => [
        'library' => [
          'search_api_federated_solr/search',
        ],
      ],
    ];

    // Ensure that this render element cache is invalidated when search app or
    // index config is updated.
    $renderer->addCacheableDependency($element, $config);
    $renderer->addCacheableDependency($element, $index_config);

    return $element;
  }

}
