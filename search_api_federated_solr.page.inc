<?php

/**
 * @file search_api_federated_solr.page.inc
 * Contains page implementations for the Federated Solr Search API Module.
 *
 * @copyright Copyright (c) 2018-19 Palantir.net
 */

/**
 * Constructs the search page.
 */
function page_search_api_federated_solr() {
  $data_federated_search_app_config = search_api_federated_solr_config_json();
  $element = [];
  $element['#markup'] = '<div id="root" data-federated-search-app-config="'.htmlspecialchars($data_federated_search_app_config).'"><noscript>This search page requires Javascript in order to function.  <a href="https://www.whatismybrowser.com/guides/how-to-enable-javascript/auto">Learn how to enable Javascript in your browser.</a></noscript><p class="element-invisible" aria-hidden="true">Federated Solr Search App: If you see this message in your DevTools, it likely means there is an issue adding the app javascript library to this page.  Follow the steps in the search_api_federated_solr module README.</p></div>';
  $element['#attached']['library'][] = ['search_api_federated_solr', 'search-app'];

  return $element;
}

/**
 * Create search_api_federated_solr config json endpoint.
 */
function search_api_federated_solr_config_json() {
  $federated_search_app_config = [];

  $search_index = variable_get('search_api_federated_solr_search_index');
  if (empty($search_index)) {
    return json_encode($federated_search_app_config);
  }
  // Determine the proper endpoint for searches.
  $proxy_is_disabled = variable_get('search_api_federated_solr_disable_query_proxy', 0);
  $federated_search_app_config['proxyIsDisabled'] = $proxy_is_disabled;

  if (!$proxy_is_disabled) {
    $options['query'] = search_api_federated_solr_proxy_params();
    $options = ['absolute' => true];
    $federated_search_app_config['url'] = url('search-api-federated-solr/search', $options);
  }
  else {
    $server_url = search_api_federated_solr_get_server_url();
    // REQUIRED: The default solr backend.
    $federated_search_app_config['url'] = $server_url;
  }

  /* OPTIONAL:
   * The username and password for Basic Authentication on the server.
   * The username and password will be
   * combined and base64 encoded as per the application.
   */
  $basic_auth_username = variable_get('search_api_federated_solr_search_index_basic_auth_username');
  $basic_auth_password = variable_get('search_api_federated_solr_search_index_basic_auth_password');
  if ($basic_auth_username || $basic_auth_password) {
    $federated_search_app_config['userpass'] = base64_encode($basic_auth_username . ':' . $basic_auth_password);
  }

  // Create an index property field map array to determine which fields
  // exist on the index and should be hidden in the app UI.
  $search_fields = [
    "sm_site_name" => [
      "property" => variable_get('search_api_federated_solr_has_site_name_property', 1),
      "is_hidden" => variable_get('search_api_federated_solr_hide_site_name'),
    ],
    "ss_federated_type" => [
      "property" =>  variable_get('search_api_federated_solr_has_federated_type_property', 1),
      "is_hidden" => variable_get('search_api_federated_solr_hide_type'),
    ],
    "ds_federated_date" => [
      "property" => variable_get('search_api_federated_solr_has_federated_date_property', 1),
      "is_hidden" => variable_get('search_api_federated_solr_hide_date'),
    ],
    "sm_federated_terms" => [
      "property" => variable_get('search_api_federated_solr_has_federated_terms_property', 1),
      "is_hidden" => variable_get('search_api_federated_solr_hide_terms'),
    ],
  ];

  // Set hiddenSearchFields to an array of keys of those $search_fields items
  // which both exist as an index property and are set to be hidden.

  // OPTIONAL: Machine name of those search fields whose facets/filter and
  // current values should be hidden in UI.
  $federated_search_app_config['hiddenSearchFields'] = array_keys(array_filter($search_fields, function ($value) {
    return $value['property'] && $value['is_hidden'];
  }));

  // OPTIONAL: The text to display when the app loads with no search term.
  $search_prompt = variable_get('search_api_federated_solr_search_prompt_text');
  if ($search_prompt) {
    $federated_search_app_config['searchPrompt'] = $search_prompt;
  }

  // OPTIONAL: The text to display when a search returns no results.
  $no_response = variable_get('search_api_federated_solr_no_results_text');
  if ($no_response) {
    $federated_search_app_config['noResults'] = $no_response;
  }

  // OPTIONAL: The text to display when a search returns no results.
  $show_empty_search_results = variable_get('search_api_federated_solr_show_empty_search_results');
  if ($show_empty_search_results) {
    $federated_search_app_config['showEmptySearchResults'] = $show_empty_search_results;
  }

  // OPTIONAL: The number of search results to show per page.
  $rows = variable_get('search_api_federated_solr_rows');
  if ($rows) {
    $federated_search_app_config['rows'] = $rows;
  }

  // OPTIONAL: The number of page buttons to show for pagination.
  $pagination_buttons = variable_get('search_api_federated_solr_page_buttons');
  if ($pagination_buttons) {
    $federated_search_app_config['paginationButtons'] = $pagination_buttons;
  }

  /** Not implemented yet in D7.
  // OPTIONAL: The rendered title of the search page.
  if ($page_title = $config->get('page_title')) {
    $federated_search_app_config['pageTitle'] = $page_title;
  }*/

  $federated_search_app_config['autocomplete'] = FALSE;
  if ($autocomplete_is_enabled = variable_get('search_api_federated_solr_autocomplete_is_enabled')) {
    // REQUIRED: Autocomplete endpoint, defaults to main search url
    if ($autocomplete_url = variable_get('search_api_federated_solr_autocomplete_url')) {
      $federated_search_app_config['autocomplete']['url'] = $autocomplete_url;
    }
    // OPTIONAL: defaults to false, whether or not to append wildcard to query term
    if ($autocomplete_append_wildcard = variable_get('search_api_federated_solr_autocomplete_is_append_wildcard')) {
      $federated_search_app_config['autocomplete']['appendWildcard'] = $autocomplete_append_wildcard;
    }
    // OPTIONAL: defaults to 5, max number of autocomplete results to return
    if ($autocomplete_suggestion_rows = variable_get('search_api_federated_solr_autocomplete_suggestion_rows')) {
      $federated_search_app_config['autocomplete']['suggestionRows'] = $autocomplete_suggestion_rows;
    }
    // OPTIONAL: defaults to 2, number of characters *after* which autocomplete results should appear
    if ($autocomplete_num_chars = variable_get('search_api_federated_solr_autocomplete_num_chars')) {
      $federated_search_app_config['autocomplete']['numChars'] = $autocomplete_num_chars;
    }
    // REQUIRED: show search-as-you-type results ('result', default) or search term ('term') suggestions
    if ($autocomplete_mode = variable_get('search_api_federated_solr_autocomplete_mode')) {
      $federated_search_app_config['autocomplete']['mode'] = $autocomplete_mode;
      // OPTIONAL: default set, title to render above autocomplete results
      if ($autocomplete_mode_title_text = variable_get('search_api_federated_solr_autocomplete_' . $autocomplete_mode . '_title_text')) {
        $federated_search_app_config['autocomplete'][$autocomplete_mode]['titleText'] = $autocomplete_mode_title_text;
      }
      // OPTIONAL: defaults to false, whether or not to hide the keyboard usage directions text
      if ($autocomplete_mode_hide_directions = variable_get('search_api_federated_solr_autocomplete_' . $autocomplete_mode . '_hide_directions_text')) {
        $federated_search_app_config['autocomplete'][$autocomplete_mode]['showDirectionsText'] = FALSE;
      }
    }
  }

  if (function_exists('domain_get_domain')) {
    $domain = domain_get_domain();
    if (isset($domain['path'])) {
      $federated_search_app_config['hostname'] =  parse_url($domain['path'], PHP_URL_HOST) ;
    }
  }

  return json_encode($federated_search_app_config, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
}
