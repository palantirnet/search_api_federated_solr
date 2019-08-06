<?php

/**
 * @file search_api_federated_solr.proxy.inc
 * Contains proxy implementations for the Federated Solr Search API Module.
 *
 * @copyright Copyright (c) 2018-19 Palantir.net
 */

/**
 * The proxy controller.
 *
 * Uses the selected index server's backend connector to execute
 * a select query on the index based on request qs params passed from the app.
 */
function search_api_federated_solr_proxy() {
  $results = [];

  // Test URL:
  // http://d7.fs-demo.local/search-api-federated-solr/search?search=soup&sm_site_name[]=Federated%20Search%20Demo%20(D8%2C%20single)&ss_federated_type=Recipe

  // Parse the querystring into an array, supporting multiples.
  $qs = str_replace(base_path() . request_path() . '?', '', request_uri());
  $qs_urldecoded = urldecode($qs);
  $params = search_api_federated_solr_parse_str_multiple($qs_urldecoded);

  $search_index = variable_get('search_api_federated_solr_search_index');
  // Get the index entity.
  /** @var \SearchApiIndex $index */
  $index = search_api_index_load($search_index);
  // Get server entity.
  /** @var \SearchApiServer $server */
  try {
    $server = $index->server();
  }
  catch (SearchApiException $e) {
    watchdog_exception('search_api_federated_solr', $e, '%type while getting server for @index: @message in %function (line %line of %file).', array('@index' => $index->label()));
  }
  // Get the solr service proxy class.
  /** @var \SearchApiSolrService $solr */
  $solr = new SearchApiSolrService($server);
  /** @var \SearchApiSolrConnection $connection */
  $connection = $solr->getSolrConnection();

  // If this is an acquia environment and if it is an acquia search server
  // get the Acquia Search Service + Connection instead.
  if (isset($_ENV['AH_SITE_ENVIRONMENT']) && module_exists('search_api_acquia') && $server->class === 'acquia_search_service') {
    /** @var \SearchApiAcquiaSearchService $solr */
    $solr = new SearchApiAcquiaSearchService($server);
    /** @var \SearchApiAcquiaSearchConnection $connection */
    $connection = $solr->getSolrConnection();
  }

  // Get the configured default query fields.
  $config_fields = variable_get('search_api_federated_solr_proxy_query_fields', ['rendered_item']);
  $query_fields = $config_fields;

  // Validate default query fields against index schema, unless flagged not to.
  $is_validate_query_fields = variable_get('search_api_federated_solr_proxy_validate_query_fields_against_schema', TRUE);
  if ($is_validate_query_fields) {
    // Get index field names mapped to their solr field name counterparts
    $backend_field_names_map = $solr->getFieldNames($index);
    // Get all full text fields from the index.
    $full_text_index_fields = $index->getFullTextFields();
    // We can only search full text fields, so validate supplied field names.
    $full_text_query_fields = array_intersect($config_fields, $full_text_index_fields);
    // Filter the field names map by our query fields.
    $query_fields_map = array_intersect_key($backend_field_names_map, array_flip($full_text_query_fields));
    // Get the solr field name for our supplied full text query fields.
    $query_fields = array_values($query_fields_map);
  }

  // Determine if we have issued a site_name query, and filter it as
  // required by the site list settings. Note that if we set a default
  // site name value, it will be passed to the proxy as an 'fq' value.
  $ignore_default = FALSE;

  if (!empty($params) && is_array($params)) {
    // Account for strings passed by the query.
    if (isset($params['fq'])) {
      if (is_string($params['fq'])) {
        $params['fq'] = [$params['fq']];
      }
    }
    else {
      $params['fq'] = [];
    }
    foreach ($params['fq'] as $key => $value) {
      if (substr_count($value, 'sm_site_name') > 0) {
        $fq = urldecode($value);
        unset($params['fq'][$key]);
        $params['fq'][] = $fq;
        $ignore_default = TRUE;
      }
    }
  }

  // If site search is restricted, enforce it here.
  if (!$ignore_default) {
    // OPTIONAL: The allowed list of sites for the search. Note that these are
    // stored as a keyed array with 0 as the default (unchecked) value. So we
    // must filter the list before setting the variable or our app breaks.
    $allowed_list = variable_get('search_api_federated_solr_allowed_sites', []);
    $allowed_sites = array_keys(array_filter($allowed_list));
    if (!empty($allowed_sites)) {
      $site_list = $allowed_sites;
    }
    if (!empty($site_list)) {
      foreach ($site_list as $name) {
        $values[] = '"'. $name .'"';
      }
      $params['fq'][] = 'sm_site_name:(' . implode(' OR ', $values) . ')';
    }
  }

  // Set facet params
  if (isset($params['facet.field']) && is_array($params['facet.field']) && count($params['facet.field'])) {
    // Set defaults for facet params if they don't already exist.
    $params += [
      'facet' => 'on',
      'facet.limit' => -1,
      'facet.sort' => 'index',
    ];
  }

  // Support empty search result set (i.e. show all results)
  if (variable_get('search_api_federated_solr_show_empty_search_results', 0)) {
    // Set the default search param to '*' if it doesn't already exist.
    $params += [
      'search' => '*',
    ];
  }

  // Add flag to debug the query if flag exists in settings.
  if (variable_get('search_api_federated_solr_proxy_debug_query', FALSE)) {
    $params += [
      'debug' => 'true',
    ];
  }

  // Merge in the default params.
  $params += [
    'start' => 0,
    'rows' => 20,
    'sort' => 'score desc',
    'hl' => 'true',
    'hl.simple.pre' => '<strong>',
    'hl.simple.post' => '</strong>',
    'hl.fl' => 'tm_rendered_item',
    'hl.usePhraseHighlighter' => 'true',
    'qf' => implode(' ', $query_fields),
  ];

  if (isset($params['search'])) {
    $query = $params['search'];
    unset($params['search']);
    try {
      $results = $connection->search(urldecode($query), $params);
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_federated_solr', $e, '%type while executing query on @server: @message in %function (line %line of %file).', array('@server' => $server->label()));
    }
  }

  // Post process results.
  if (isset($results)) {
    // Convert each facet_fields->item into an array, i.e.:
    // FROM object:
    // sm_site_name: {
    //   Federated SOLR D7 = 0,
    //   Federated Search Demo (D8, Single) = 0
    // }
    // TO ARRAY:
    // sm_site_name: [
    //   "Federated SOLR D7",
    //   0,
    //   "Federated Search Demo (D8, single)",
    //   0
    // ]
    if (isset($results->facet_counts->facet_fields) && $facet_fields = $results->facet_counts->facet_fields) {
      foreach ($facet_fields as $field => $value) {
        $facet_fields_array = [];
        foreach ($value as $facet_key => $facet_value) {
          array_push($facet_fields_array, $facet_key);
          array_push($facet_fields_array, $facet_value);
        }
        $facet_fields->$field = $facet_fields_array;
      }
    }
  }

  /* Test formatting.
  $response = (object) [
    'response' => $results->response,
    'facet_counts' => $results->facet_counts,
    'highlighting' => $results->highlighting,
  ];*/

  return drupal_json_output($results);
}
