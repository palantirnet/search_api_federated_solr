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

  $qs = $_GET;
  unset($qs['q']);
  $data += $qs;
  // Parse the querystring, with support for multiple values for a key,
  // not using array[] syntax.
  // We may not need this function.
  // $params = search_api_federated_solr_parse_multiple($qs);

  return drupal_json_output($data);
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
