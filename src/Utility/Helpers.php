<?php

namespace Drupal\search_api_federated_solr\Utility;

use Drupal\Core\Url;

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
    // Get the id of the chosen index's server.
    $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');
    $index_id = $app_config->get('index.id');
    // Get index config.
    $index_config = \Drupal::config('search_api.index.' . $index_id);
    // Get the index's server name.
    $index_server = $index_config->get('server');
    // Get the server config.
    $server_config = \Drupal::config('search_api.server.' . $index_server);
    // Get the server's backend connector config.
    $server = $server_config->get('backend_config.connector_config');
    // Compute the server URL.
    // Get the required server config field data.
    $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
    // Check for the non-required server config field data before appending.
    $server_url .= $server['path'] ?: '';
    $server_url .= $server['core'] ? '/' . $server['core'] : '';
    // Append the request handler, main query and format params.
    $server_url .= '/select';

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
    $proxy_url_object = Url::fromRoute('search_api_federated_solr.solr_proxy', [], $proxy_url_options);
    $proxy_url = $proxy_url_object->toString();

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

    // Append qs params for block form autocomplete js.
    if ($qs) {
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
  public static function parse_str_multiple($str) {
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
}

