<?php

namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides route responses for the search results page.
 */
class SearchController extends ControllerBase {
  /**
   * Determine the URL for the server of the selected index.
   * @param $index_id
   *
   * @return string
   */
  private function get_server_url($index_id) {
    $index_config = \Drupal::config('search_api.index.' . $index_id);
    $index_server = $index_config->get('server');
    // Get the server url.
    $server_config = \Drupal::config('search_api.server.' . $index_server);
    $server = $server_config->get('backend_config.connector_config');
    // Get the required server config field data.
    $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
    // Check for the non-required server config field data before appending.
    $server_url .= $server['path'] ?: '';
    $server_url .= $server['core'] ? '/' . $server['core'] : '';
    // Append the request handler, main query and format params.
    $server_url .= '/select';

    return $server_url;
  }

  /**
   * Determines url to use for app search + autocomplete queries based on config:
   *  - is the proxy enabled?
   *    - yes: compute absolute url to proxy route, append qs params
   *    - no: was an direct url endpoint passed?
   *      - yes: use that endpoint
   *      - no: compute the server url, qs params
   *
   * @param integer $proxy_is_disabled
   *   Flag indicating whether or not the autocomplete proxy is disabled (0 || 1)
   * @param string $direct_url
   *   Value of the direct url ("" || <absolute-url-with-qs-params>)
   * @param string $index_id
   *   The id of the chosen index
   *
   * @return string
   */
  private function get_endpoint($proxy_is_disabled, $direct_url, $index_id) {
    if ($proxy_is_disabled) {
      // Use a direct url if one was passed in.
      $endpoint_url = $direct_url;

      // Fall back to the server URL
      if (!$endpoint_url) {
        $endpoint_url = $this->get_server_url($index_id);
      }
    } else {
      // Compute the proxy URL
      $proxy_url_options = [
        'absolute' => TRUE,
      ];
      $proxy_url_object = Url::fromRoute('search_api_federated_solr.solr_proxy', [], $proxy_url_options);
      $proxy_url = $proxy_url_object->toString();
      $endpoint_url = $proxy_url;
    }

    return $endpoint_url;
  }

  /**
   * Returns a content for a search page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function content() {
    $renderer = \Drupal::service('renderer');

    $config = \Drupal::configFactory()->getEditable('search_api_federated_solr.search_app.settings');
    $index_config = \Drupal::config('search_api.index.' . $config->get('index.id'));

    // Define the federated search app configuration array.
    // The twig template will json_encode this array into the object expected by
    // the search app: https://github.com/palantirnet/federated-search-react/blob/master/src/.env.local.js.example
    $federated_search_app_config = [];

    // Determine the URL by calling get_endpoint with no direct_url option
    //   because we don't accept a custom direct endpoint for search.
    $proxy_is_disabled = $config->get('proxy.isDisabled') || 0;
    $index_id = $config->get('index.id');
    $url = $this->get_endpoint($proxy_is_disabled, '', $index_id);

    $federated_search_app_config['proxyIsDisabled'] = $proxy_is_disabled;
    $federated_search_app_config['url'] = $url;

    /*
     * OPTIONAL:
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

    // Create an index property field map array to determine which fields
    // exist on the index and should be hidden in the app UI.
    $search_fields = [
      "sm_site_name" => [
        "property" => $site_name_property,
        "is_hidden" => $config->get('facet.site_name.is_hidden'),
      ],
      "ss_federated_type" => [
        "property" =>  $config->get('index.has_federated_type_property'),
        "is_hidden" => $config->get('facet.federated_type.is_hidden'),
      ],
      "ds_federated_date" => [
        "property" => $config->get('index.has_federated_date_property'),
        "is_hidden" => $config->get('filter.federated_date.is_hidden'),
      ],
      "sm_federated_terms" => [
        "property" => $config->get('index.has_federated_terms_property'),
        "is_hidden" => $config->get('facet.federated_terms.is_hidden'),
      ],
    ];

    // Set hiddenSearchFields to an array of keys of those $search_fields items
    // which both exist as an index property and are set to be hidden.

    // OPTIONAL: Machine name of those search fields whose facets/filter and
    // current values should be hidden in UI.
    $federated_search_app_config['hiddenSearchFields'] = array_keys(array_filter($search_fields, function  ($value) {
      return $value['property'] && $value['is_hidden'];
    }));

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

      $autocomplete_proxy_is_disabled = $config->get('autocomplete.proxy.isDisabled') || 0;
      $federated_search_app_config['autocomplete']['proxyIsDisabled'] = $autocomplete_proxy_is_disabled;

      $autocomplete_direct_url = $config->get('autocomplete.direct.url');
      $autocomplete_url = $this->get_endpoint($autocomplete_proxy_is_disabled, $autocomplete_direct_url, $index_id);

      if ($autocomplete_url) {
        $federated_search_app_config['autocomplete']['url'] = $autocomplete_url;
      }
      if ($autocomplete_username = $config->get('autocomplete.username') && $autocomplete_password = $config->get('autocomplete.password')) {
        $federated_search_app_config['autocomplete']['userpass'] = base64_encode($autocomplete_username . ':' . $autocomplete_password);
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
