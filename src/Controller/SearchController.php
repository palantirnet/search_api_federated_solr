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

    /* OPTIONAL:
     * The username and password for Basic Authentication on the server.
     * The username and password will be
     * combined and base64 encoded as per the application.
     */
    $username = $config->get('index.username');
    $pass = $config->get('index.password');
    $federated_search_app_config['userpass'] = $username && $pass ? base64_encode($config->get('index.username') . ':' . $config->get('index.password')) : '';

    // Validate that there is still a site name property set for this index.
    $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
    $config->set('index.has_site_name_property', $site_name_property ? TRUE : FALSE);

    // Determine if config option to set default site name is set.
    $set_default_site = $config->get('facet.site_name.set_default');

    /* We no longer have a site name property so unset the set default config.
     * See Drupal\search_api_federated_solr\Form\FederatedSearchPageForm class
     * The default "Site Name" facet value is passed by search form
     * in initial get request.
     */
    if ($set_default_site && !$site_name_property) {
      $config->set('facet.site_name.set_default', 0);
    }

    // OPTIONAL: The text to display when the app loads with no search term.
    if ($search_prompt = $config->get('content.search_prompt')) {
      $federated_search_app_config['searchPrompt'] = $search_prompt;
    }

    // OPTIONAL: The text to display when a search returns no results.
    if ($no_results = $config->get('content.no_results')) {
      $federated_search_app_config['noResults'] = $no_results;
    }

    // OPTIONAL: The text to display when a search returns no results.
    if ($show_empty_search_results = $config->get('content.show_empty_search_results')) {
      $federated_search_app_config['showEmptySearchResults'] = $show_empty_search_results;
    }

    // OPTIONAL: The number of search results to show per page.
    if ($rows = $config->get('results.rows')) {
      $federated_search_app_config['rows'] = intval($rows);
    }

    // OPTIONAL: The number of page buttons to show for pagination.
    if ($pagination_buttons = $config->get('pagination.buttons')) {
      $federated_search_app_config['paginationButtons'] = intval($pagination_buttons);
    }

    // OPTIONAL: The rendered title of the search page.
    if ($page_title = $config->get('page_title')) {
      $federated_search_app_config['pageTitle'] = $page_title;
    }

    $federated_search_app_config['autocomplete'] = FALSE;
    if ($autocomplete_is_enabled = $config->get('autocomplete.isEnabled')) {
      // REQUIRED: Autocomplete endpoint, defaults to main search url
      if ($autocomplete_url = $config->get('autocomplete.url')) {
        $federated_search_app_config['autocomplete']['url'] = $autocomplete_url;
      }
      // OPTIONAL: defaults to false, whether or not to append wildcard to query term
      if ($autocomplete_append_wildcard = $config->get('autocomplete.appendWildcard')) {
        $federated_search_app_config['autocomplete']['appendWildcard'] = $autocomplete_append_wildcard;
      }
      // OPTIONAL: defaults to 5, max number of autocomplete results to return
      if ($autocomplete_suggestion_rows = $config->get('autocomplete.suggestionRows')) {
        $federated_search_app_config['autocomplete']['suggestionRows'] = $autocomplete_suggestion_rows;
      }
      // OPTIONAL: defaults to 2, number of characters *after* which autocomplete results should appear
      if ($autocomplete_num_chars = $config->get('autocomplete.numChars')) {
        $federated_search_app_config['autocomplete']['numChars'] = $autocomplete_num_chars;
      }
      // REQUIRED: show search-as-you-type results ('result', default) or search term ('term') suggestions
      if ($autocomplete_mode = $config->get('autocomplete.mode')) {
        $federated_search_app_config['autocomplete']['mode'] = $autocomplete_mode;
        // OPTIONAL: default set, title to render above autocomplete results
        if ($autocomplete_mode_title_text = $config->get('autocomplete.' . $autocomplete_mode . '.titleText')) {
          $federated_search_app_config['autocomplete'][$autocomplete_mode]['titleText'] = $autocomplete_mode_title_text;
        }
        // OPTIONAL: defaults to false, whether or not to hide the keyboard usage directions text
        if ($autocomplete_mode_hide_directions = $config->get('autocomplete.' . $autocomplete_mode . '.hideDirectionsText')) {
          $federated_search_app_config['autocomplete'][$autocomplete_mode]['showDirectionsText'] = FALSE;
        }
      }
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
