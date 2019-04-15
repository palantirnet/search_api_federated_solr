<?php

/**
 * @file
 * Drush commands for Search API Federated Solr.
 */

use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;

/**
 * Implements hook_drush_command().
 */
function search_api_federated_solr_drush_command() {
  $items = [];

  $items['search-api-federated-solr-fields'] = [
    'description' => 'List all fields, as they map to solr machine names, for the search api index used by the federated search app.',
    'examples' => [
      'drush sapifs-f' => dt('List all fields, as they map to solr machine names, for the search api index used by the federated search app.'),
    ],
    'aliases' => ['sapifs-f'],
  ];

  return $items;
}

/**
 * List all search indexes.
 */
function drush_search_api_federated_solr_fields() {
  // Get index id from search app config.
  $config = \Drupal::configFactory()->get('search_api_federated_solr.search_app.settings');
  $index_id = $config->get('index.id');

  if (!$index_id) {
    drush_print('In order to return the solr field names, please choose a search api index in the federated search app settings.');
    return;
  }

  // Get the server id from index config.
  $index_config = Drupal::config('search_api.index.' . $index_id);
  $server_id = $index_config->get('server');
  // Load the server.
  /** @var \Drupal\search_api\ServerInterface $server */
  $server = Server::load($server_id);
  try {
    /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
    $backend = $server->getBackend();
  }
  catch (SearchApiException $e) {
    drush_print('Could not connect to the Solr backend for the search api server.');
    return;
  }

  // Load the index.
  $indexes = $server->getIndexes();
  /** @var \Drupal\search_api\IndexInterface $federated_search_index */
  $federated_search_index = $indexes[$index_id];

  // Get index field names mapped to their solr field name counterparts
  $field_names = $backend->getSolrFieldNames($federated_search_index);

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
