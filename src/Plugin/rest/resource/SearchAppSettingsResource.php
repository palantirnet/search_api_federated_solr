<?php

namespace Drupal\search_api_federated_solr\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Search App Settings Resource
 *
 * @RestResource(
 *   id = "search_app_settings_resource",
 *   label = @Translation("Search App Settings Resource"),
 *   uri_paths = {
 *     "canonical" = "/search_api_federated_solr/settings"
 *   }
 * )
 */
class SearchAppSettingsResource extends ResourceBase {
  /**
   * Responds to GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $server_config = \Drupal::config('search_api.server.federated_search_index');
    $server = $server_config->get('backend_config.connector_config');
    // Get the required server config field data.
    $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
    // Check for the non-required server config field data before appending.
    $server_url .= $server['path'] ?: '';
    $server_url .= $server['core'] ? '/' . $server['core'] : '';
    // Append the request handler.
    $server_url .= '/select';

    $index_config = \Drupal::config('search_api.index.federated_search_index');
    $site_name = $index_config->get('field_settings.site_name.configuration.site_name');

    $response = [
      'url' => $server_url,
      'siteSearch' => $site_name,
    ];
    return new ResourceResponse($response);
  }
}
