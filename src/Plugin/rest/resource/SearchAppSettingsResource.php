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
    $response = ['siteSearch' => 'University of Michigan Lab Blog'];
    return new ResourceResponse($response);
  }
}
