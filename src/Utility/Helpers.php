<?php

namespace Drupal\search_api_federated_solr\Utility;

use Drupal\Core\Url;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;

/**
 * Contains helper methods for the Search API Federated Solr module.
 */
class Helpers {
  /**
   * Determines URL for select handler of the selected index backend
   *
   * @return string
   *   URL for the solr backend /select request handler
   */
  public static function getSelectHandlerUrl() {
    $server_url = '';

    // Get the id of the chosen index's server.
    $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');
    $index_id = $app_config->get('index.id');
    // Get index config.
    $index_config = \Drupal::config('search_api.index.' . $index_id);
    // Get the index's server name.
    $server_id = $index_config->get('server');
    // Load the server.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load($server_id);
    try {
      /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
      $backend = $server->getBackend();
      /** @var \Drupal\search_api_solr\SolrConnectorInterface $connector */
      $connector = $backend->getSolrConnector();
      $server_link = $connector->getServerLink();
      $server_url = $server_link->getUrl()->toUriString();
      // Get the server's solr core.
      $core = $connector->getConfiguration()['core'];
      $server_url .= $core;
      // Append the request handler, main query and format params.
      $server_url .= '/select';
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_federated_solr', $e, '%type while getting backend + connector for @server: @message in %function (line %line of %file).', array('@server' => $server->label()));
    }

    return $server_url;
  }

  /**
   * Determines url to use for app search + autocomplete queries based on config:
   *  - defaults to absolute url to proxy route, appends qs params
   *  - if proxy disabled
   *    - compute and fallback to the server url
   *    - if direct url endpoint passed, use it
   *
   * @param integer $proxy_is_disabled
   *   Flag indicating whether or not the autocomplete proxy is disabled (0 || 1)
   * @param string $direct_url
   *   Value of the direct url ("" || <absolute-url-with-qs-params>)
   * @param string $qs
   *   Querystring params to append to proxy url
   *
   * @return string
   *   URL for the endpoint to be used for query requests.
   */
  public static function getEndpointUrl($proxy_is_disabled, $direct_url, $qs = '') {
    // Create proxy URL.
    $proxy_url_options = [
      'absolute' => TRUE,
    ];
    $proxy_link = Url::fromRoute('search_api_federated_solr.solr_proxy', [], $proxy_url_options);
    $proxy_url = $proxy_link->toString();

    // Default to proxy url.
    $endpoint_url = $proxy_url;

    if ($proxy_is_disabled) {
      // Override with direct URL if provided.
      if ($direct_url) {
        $endpoint_url = $direct_url;
      }
      else {
        // Fallback to solr backend select handler URL.
        $endpoint_url = self::getSelectHandlerUrl();
      }
    }

    // Append qs params for block form autocomplete js unless configured
    // with a direct url (like a view rest export endpoint).
    if ($qs && !$direct_url) {
      $endpoint_url .= $qs;
    }

    return $endpoint_url;
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
   */
  public static function parseStrMultiple($str) {
    # result array
    $arr = [];

    # split on outer delimiter
    $pairs = explode('&', $str);

    # loop through each pair
    foreach ($pairs as $i) {
      # split into name and value
      list($name,$value) = explode('=', $i, 2);

      # if name already exists
      if (isset($arr[$name])) {
        # stick multiple values into an array
        if (is_array($arr[$name])) {
          $arr[$name][] = $value;
        }
        else {
          $arr[$name] = array($arr[$name], $value);
        }
      }
      # otherwise, simply stick it in a scalar
      else {
        $arr[$name] = $value;
      }
    }

    # return result array
    return $arr;
  }

  /**
   * Returns the active sitename value for this site.
   *
   * @return string
   */
  public static function getSiteName() {
    // Default value.
    $site_config = \Drupal::config('system.site');
    $default_name = $site_name = $site_config->get('name');
    // Config options.
    $config = \Drupal::config('search_api_federated_solr.search_app.settings');
    // Get index id from search app config.
    $index_id = $config->get('index.id');
    // Get the server id from index config.
    $index_config = \Drupal::config('search_api.index.' . $index_id);
    $server_id = $index_config->get('server');
    // Load the server.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load($server_id);
    $indexes = $server->getIndexes();
    if (isset($indexes[$index_id])) {
      $federated_search_index = $indexes[$index_id];
      // Get the configuration.
      if ($field = $federated_search_index->getField('site_name')) {
        $site_name_config = $field->getConfiguration();
      }
      // @TODO: Handle domain access properly.
      if (defined('DOMAIN_ACCESS_FIELD')) {
        $manager = \Drupal::service('domain.negotiator');
        $active_domain = $manager->getActiveDomain();
        $site_name = $active_domain->label();
      }

      // Use the site name value from the index site name property.
      if (is_array($site_name_config) && array_key_exists('site_name', $site_name_config)) {
        $site_name = $site_name_config['site_name'];
      }

      // If the index site name property indicates using the system site name
      // then use that instead.
      if (is_array($site_name_config) && array_key_exists('use_system_site_name', $site_name_config) && $site_name_config['use_system_site_name']) {
        $site_name = $default_name;
      }
    }
    return $site_name;
  }

}

