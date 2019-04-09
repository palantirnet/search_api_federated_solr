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
  $data = [];

  // @TODO: Get server config.
  // Test URL:
  // http://d7.fs-demo.local/search-api-federated-solr/search?search=soup&sm_site_name[]=Federated%20Search%20Demo%20(D8%2C%20single)&ss_federated_type=Recipe

  $params = search_api_federated_solr_proxy_params();

  // Parse the querystring, with support for multiple values for a key,
  // not using array[] syntax.
  // We may not need this function.
  // $params = search_api_federated_solr_parse_multiple($qs);

  // Create the select query.
  // Note: this proxy will only execute select queries.
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

  // Set the base query.
  $options['parse mode'] = 'direct';

  $query = search_api_query($search_index, $options);

  // Get index field names mapped to their solr field name counterparts
  $backend_field_names_map = $solr->getFieldNames($index);
  // Get the eligible text fields for the query.
  $config_fields = variable_get('search_api_federated_solr_query_fields', ['rendered_item']);
  // Get all full text fields from the index.
  $full_text_index_fields = $index->getFullTextFields();
  // We can only search full text fields, so validate supplied field names.
  $full_text_query_fields = array_intersect($config_fields, $full_text_index_fields);
  // Filter the field names map by our query fields.
  $query_fields_map = array_intersect_key($backend_field_names_map, array_flip($full_text_query_fields));
  // Get the solr field name for our supplied full text query fields.
  $query_fields = array_values($query_fields_map);

  // @TODO EDismax fields?

  // Merge in defaults.
  $params += [
    'start' => 0,
    'rows' => 20,
    'sort' => 'score desc',
    'hl' => 'true',
    'hl.simple.pre' => urldecode('<strong>'),
    'hl.simple.post' => urldecode('</strong>'),
    'hl.fl' => 'tm_rendered_item',
    'hl.usePhraseHighlighter' => 'true',
    'facet.limit' => -1,
    'facet.sort' => 'index',
    'df' => implode(' ', $query_fields),
  ];

  $results = [];

  if (isset($params['search'])) {
    $query = $params['search'];
    unset($params['search']);
    $results = $connection->search(urldecode($query), $params);
  }

  /* Test formatting.
  $response = (object) [
    'response' => $results->response,
    'facet_counts' => $results->facet_counts,
    'highlighting' => $results->highlighting,
  ];*/

  return drupal_json_output($results);
}
