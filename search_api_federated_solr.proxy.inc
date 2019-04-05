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
  // Get the index configuration object.
  $index = search_api_index_load($search_index);

  $connection = $index->server()->getSolrConnection();

  // Set the base query.
  $options['parse mode'] = 'direct';

  $query = search_api_query($search_index, $options);

  // Get the eligible text fields for the query.
  $config_fields = variable_get('search_api_federated_solr_query_fields', ['rendered_item']);
  $index_fields = $index->options['fields'];
  foreach ($index_fields as $field => $value) {
    if ($value['type'] == 'text' && in_array($field, $config_fields, TRUE)) {
      $params['fields'][] = $field;
    }
  }
  // Get fields from Solr?
  $cf = $connection->getFields();

  // @TODO EDismax fields?

  // Merge in defaults.
  $params += [
    'start' => 0,
    'rows' => 20,
    #'sort' => 'score=desc', // throws fatal error.
    'hl.fl' => 'tm_rendered_item',
    'hl.usePhraseHighlighter' => TRUE,
    'facet.limit' => -1,
    'facet.sort' => 'index',
    'queryfields' => $params['fields'], // EDmisMax? We don't have Solarium here.
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

/**
 * Parses a querystring with support for multiple keys not using array[] syntax.
 * @see: http://php.net/manual/en/function.parse-str.php#76792
 *
 * @param $str
 *  The querystring from the request object.
 *
 * @return array
 *  Array of querystring params and their values.

function search_api_federated_solr_parse_multiple($qs) {

} */
