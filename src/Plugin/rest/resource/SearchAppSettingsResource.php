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
    $response_data = [];

    $config = \Drupal::configFactory()->getEditable('search_api_federated_solr.search_app.settings');
    $index_config = \Drupal::config('search_api.index.' . $config->get('index.id'));

    $server_url = $config->get('index.server_url');
    if ($server_url) {
      $response_data['url'] = $server_url;
    }

    // Validate that there is still a site name property set for this index.
    $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
    $config->set('index.has_site_name_property', $site_name_property ? true : false);
    // Determine if config option to set default site name is set.
    $set_default_site = $config->get('facet.site_name.set_default');

    // If default site flag is set and the index has a site property, update response.
    if ($set_default_site && $site_name_property) {
      $response_data['siteSearch'] = $site_name_property;
    }
    if ($set_default_site && !$site_name_property) {
      // We no longer have a site name property so unset the set default config.
      $config->set('facet.site_name.set_default', 0);
    }

    $no_response = $config->get('content.no_results');
    if ($no_response) {
      $response_data['noResults'] = $no_response;
    }

    $search_prompt = $config->get('content.search_prompt');
    if ($search_prompt) {
      $response_data['searchPrompt'] = $search_prompt;
    }

    $config->save();

    $response = new ResourceResponse($response_data);

    // Ensure that when the search app / index config changes, cache for this response is invalidated.
    $response->addCacheableDependency($config);
    $response->addCacheableDependency($index_config);

    return $response;
  }
}
