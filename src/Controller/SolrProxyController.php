<?php

namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_federated_solr\Utility\Helpers;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SolrProxyController extends ControllerBase {
  /**
   * Uses the selected index server's backend connector to execute
   * a select query on the index based on request qs params passed from the app.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *  Structure mirrors the solr api response object written with the JSON
   *    Response Writer with the addition of a '#cache' key for cache metadata.
   *  @see https://lucene.apache.org/solr/guide/7_2/response-writers.html#json-response-writer
   */
  public function getResultsJson(Request $request) {
    $data = [];
    // \Drupal\Core\Controller\ControllerBase::config loads config with overrides
    $config = $this->config('search_api_federated_solr.search_app.settings');
    // Get index id from search app config.
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
    $params = Helpers::parseStrMultiple($qs);

    try {
      /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
      $backend = $server->getBackend();
      /** @var \Drupal\search_api_solr\SolrConnectorInterface $connector */
      $connector = $backend->getSolrConnector();

      // Create the select query.
      // Note: this proxy will only execute select queries.
      // @see: https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/building-a-select-query/
      $query = $connector->getSelectQuery();

      // Use supplied query fields if configured in settings.php.
      $query_fields_config = $config->get('index.query_fields');
      // Default to passed in query fields, if there are any.
      $query_fields = is_array($query_fields_config) && !empty($query_fields_config) ? $query_fields_config : [];
      // Determine if we should validate passed in query fields against the schema.
      $is_validate_query_fields = $config->get('index.validate_query_fields');
      if ($is_validate_query_fields && is_array($query_fields_config) && !empty($query_fields_config)) {
        // Load the index.
        $indexes = $server->getIndexes();
        /** @var \Drupal\search_api\IndexInterface $federated_search_index */
        $federated_search_index = $indexes[$index_id];

        // Get index field names mapped to their solr field name counterparts
        $backend_field_names_map = $backend->getSolrFieldNames($federated_search_index);
        // Get all full text fields from the index.
        $full_text_fields = $federated_search_index->getFulltextFields();
        // We can only search full text fields, so validate supplied field names.
        $full_text_query_fields = array_intersect($query_fields_config, $full_text_fields);
        // Filter the field names map by our query fields.
        $query_fields_map = array_intersect_key($backend_field_names_map, array_flip($full_text_query_fields));
        // Get the solr field name for our supplied full text query fields.
        $query_fields = array_values($query_fields_map);
      }

      // If there are any query fields, add them to the query.
      if (!empty($query_fields)) {
        // Get edismax query parser (used by the default request handler).
        $edismax = $query->getEDisMax();
        // Set default query fields, overriding solr config.
        $edismax->setQueryFields(implode(' ', $query_fields));
      }

      // Determine if we should add debug info to the proxy response object.
      $is_debug = $config->get('proxy.debug');
      if ($is_debug) {
        $debug = $query->getDebug();
      }

      // If site search is restricted, enforce it here.
      $restrict_site = $config->get('facet.site_name.set_default')
                    && $config->get('facet.site_name.is_hidden');
      if ($restrict_site) {
        if (!empty($params['fq']) && !is_array($params['fq'])) {
          $params['fq'] = array($params['fq']);
        }
        elseif(empty($params['fq'])) {
          $params['fq'] = array();
        }
        foreach ($params['fq'] as $id => $element) {
          if (substr_count($element, 'sm_site_name:') > 0) {
            unset($params['fq'][$id]);
          }
        }
        // Load the index if needed. @TODO: Make this a method.
        if (!isset($federated_search_index)) {
          // Load the index.
          $indexes = $server->getIndexes();
          /** @var \Drupal\search_api\IndexInterface $federated_search_index */
          $federated_search_index = $indexes[$index_id];
        }
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
        // The site name can be configured as part of the property.
        // Determine the correct value.
        
        // Use the site name value from the index site name property.
        if (is_array($site_name_config) && array_key_exists('site_name', $site_name_config)) {
          $site_name = $site_name_config['site_name'];
        }

        // If the index site name property indicates using the system site name
        // then use that instead.
        if (is_array($site_name_config) && array_key_exists('use_system_site_name', $site_name_config) && $site_name_config['use_system_site_name']) {
          $site_config = $this->config('system.site');
          $site_name = $site_config->get('name');
        }
        $params['fq'][] = 'sm_site_name:("' . $site_name . '")';
      }

      // Set main query param.
      $q = is_array($params) && array_key_exists('q', $params) ? urldecode($params['q']) : '*';
      $query->setQuery($q);

      // Set query conditions.
      $start = is_array($params) && array_key_exists('start', $params) ? $params['start'] : 0;
      $rows = is_array($params) && array_key_exists('rows', $params) ? $params['rows'] : 20;
      // Set query start + number of results.
      $query->setStart($start)->setRows($rows);

      // Set query sort, default to score (relevance).
      // Note: app only supports 1 sort at a time: date or score, desc
      $sort = is_array($params) && array_key_exists('sort', $params) ? urldecode($params['sort']) : 'score=desc';
      if ($sort_parts = explode("=", $sort)) {
        $query->setSorts([$sort_parts[0] => $sort_parts[1]]);
      }

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
          // Sets a unique key for filter queries <facet.field>=<value> (required),
          // then sets query value <facet.field>:<value>
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

    // Create json response with 200 response code.
    $response = new JsonResponse($data, 200);
    return $response;
  }
}
