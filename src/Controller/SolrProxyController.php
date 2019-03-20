<?php

namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;
use Symfony\Component\HttpFoundation\Request;

class SolrProxyController extends ControllerBase {

  /**
   * Parses a querystring with support for multiple keys not using array[] syntax.
   * @see: http://php.net/manual/en/function.parse-str.php#76792
   *
   * @param $str
   *
   * @return array
   */
  private static function parse_str_multiple($str) {
    # result array
    $arr = [];

    # split on outer delimiter
    $pairs = explode('&', $str);

    # loop through each pair
    foreach ($pairs as $i) {
      # split into name and value
      list($name,$value) = explode('=', $i, 2);

      # if name already exists
      if( isset($arr[$name]) ) {
        # stick multiple values into an array
        if( is_array($arr[$name]) ) {
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

  public function getResultsJson(Request $request) {
    $data = [];
    // Get index id from search app config.
    $config = \Drupal::configFactory()->getEditable('search_api_federated_solr.search_app.settings');
    $index_id = $config->get('index.id');
    // Get the server id from index config.
    $index_config = \Drupal::config('search_api.index.' . $index_id);
    $server_id = $index_config->get('server');
    // Load the server.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load($server_id);

    // Get query data from route variables.
    $qs = $request->getQueryString();
    // Parse the querystring, with support for multiple values for a key,
    // not using array[] syntax.
    // Can't use \Drupal\Core\Routing\RouteMatchInterface::getParameters()
    //   because the route doesn't / can't define qs params as parameters.
    // Can't use \Drupal\Component\Utility\UrlHelper::parse() because it uses
    //   str_parse which requires array brackets [] syntax for param keys with
    //   multiple values and that is not the syntax that solr expects.
    // @see: http://php.net/manual/en/function.parse-str.php#76792
    $params = self::parse_str_multiple($qs);

    try {
      /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
      $backend = $server->getBackend();
      /** @var \Drupal\search_api_solr\SolrConnectorInterface $connector */
      $connector = $backend->getSolrConnector();

      // Create the select query.
      // Note: this proxy will only execute select queries.
      $query = $connector->getSelectQuery();
      // $debug = $query->getDebug(); // uncomment to enable $query_response->getDebug();

      // Set main query param.
      $q = is_array($params) && array_key_exists('q', $params) ? $params['q'] : '*';
      $query->setQuery($q);

      // Set query conditions.
      $start = is_array($params) && array_key_exists('start', $params) ? $params['start'] : 0;
      $rows = is_array($params) && array_key_exists('rows', $params) ? $params['rows'] : 20;
      // Set query start + number of results.
      $query->setStart($start)->setRows($rows);

      // Configure highlight component.
      $hl_field = array_key_exists('hl.fl', $params) ? $params['hl.fl'] : 'tm_rendered_item';
      $hl_use_phrase_highlighter = array_key_exists('hl.usePhraseHighlighter', $params) ? $params['hl.usePhraseHighlighter'] : TRUE;

      $hl = $query->getHighlighting();
        $hl->setFields($hl_field);
        $hl->setSimplePrefix('<strong>');
        $hl->setSimplePostfix('</strong>');
        $hl->setUsePhraseHighlighter($hl_use_phrase_highlighter);

      // Configure FacetSet component.
      $facet_set = $query->getFacetSet();

      // Set FacetSet limit + sort.
      $facet_limit = is_array($params) && array_key_exists('facet.limit', $params) ? $params['facet.limit'] : -1;
      $facet_sort = is_array($params) && array_key_exists('facet.sort', $params) ? $params['facet.sort'] : 'index';
      $facet_set->setLimit($facet_limit);
      $facet_set->setSort($facet_sort);

      // Create FacetSet fields.
      if (is_array($params) && array_key_exists('facet.field', $params) && is_array($params['facet.field'])) {
        foreach ($params['facet.field'] as $facet_field) {
          $facet_set->createFacetField($facet_field)->setField($facet_field);
        }
      }

      // Create Filter Queries.
      if (is_array($params) && array_key_exists('fq', $params)) {
        // When there is only 1 filter query, make it an array.
        if ( !is_array($params['fq'])) {
          $fq = $params['fq'];
          $params['fq'] = [$fq];
        }
        // Write filter queries.
        foreach ($params['fq'] as $fq) {
          $fq = urldecode($fq);
          $parts = explode(':', $fq);
          $query->createFilterQuery($parts[0] . '=' . $parts[1])->setQuery($fq);
        }
      }

      // Fetch results.
      $query_response = $connector->execute($query);
      $data = $query_response->getData();
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_federated_solr', $e, '%type while executed query on @server: @message in %function (line %line of %file).', array('@server' => $server->label()));
    }


    // Add Cache settings for Max-age and URL context.
    // You can use any of Drupal's contexts, tags, and time.
    $data['#cache'] = [
      'max-age' => 0,
      'contexts' => [
        'url',
      ],
    ];
    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));
    return $response;
  }
}
