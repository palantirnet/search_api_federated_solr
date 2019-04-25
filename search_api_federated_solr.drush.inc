<?php

/**
 * @file
 * Drush commands for Search API Federated Solr.
 */

/**
 * Implements hook_drush_command().
 */
function search_api_federated_solr_drush_command() {
  $items = array();

  $items['search-api-federated-solr-fields'] = array(
    'description' => 'List all fields, as they map to solr machine names, for a given index.',
    'examples' => array(
      'drush sapifs-f' => dt('List all fields, as they map to solr machine names, for a given index.'),
    ),
    'aliases' => array('sapifs-f'),
  );

  return $items;
}


/**
 * List all fields, as they map to solr machine names, for a given index.
 */
function drush_search_api_federated_solr_fields() {
  if (search_api_federated_solr_drush_static(__FUNCTION__)) {
    return;
  }

  $search_index = variable_get('search_api_federated_solr_search_index');
  // Get the index entity.
  /** @var \SearchApiIndex $index */
  $index = search_api_index_load($search_index);
  if (!$index) {
    drush_print(dt('The search app index could not be loaded.'));
    return;
  }
  // Get server entity.
  /** @var \SearchApiServer $server */
  try {
    $server = $index->server();
  }
  catch (SearchApiException $e) {
    drush_print('The search server could not be loaded.');
    return;
  }
  // Get the search api solr service class.
  /** @var \SearchApiSolrService $solr */
  $solr = new SearchApiSolrService($server);

  // If this is an acquia environment and if it is an acquia search server
  // get the Acquia Search Service instead.
  if (isset($_ENV['AH_SITE_ENVIRONMENT']) && module_exists('search_api_acquia') && $server->class === 'acquia_search_service') {
    /** @var \SearchApiAcquiaSearchService $solr */
    $solr = new SearchApiAcquiaSearchService($server);
  }

  // Get the index field names as they map to solr names.
  $field_names = $solr->getFieldNames($index);

  $rows[] = array(
    dt('Index Field Machine Name'),
    dt('| Solr Field Machine Name'),
  );

  $rows[] = array(
    dt('-------------------------'),
    dt('| -----------------------'),
  );

  foreach ($field_names as $key => $value) {
    $row = array(
      $key,
      '| ' . $value,
    );
    $rows[] = $row;
  }
  drush_print_table($rows);
}

/**
 * Does a static lookup to prevent Drush 4 from running twice.
 *
 * @param string $function
 *   The Drush function being called.
 *
 * @return bool
 *   TRUE if the function was already called in this Drush execution, FALSE
 *   otherwise.
 *
 * @see http://drupal.org/node/704848
 */
function search_api_federated_solr_drush_static($function) {
  static $index = array();
  if (isset($index[$function])) {
    return TRUE;
  }
  $index[$function] = TRUE;
  return FALSE;
}
