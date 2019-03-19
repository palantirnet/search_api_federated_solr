<?php

namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Query;
use Symfony\Component\HttpFoundation\Request;

class SolrProxyController extends ControllerBase {

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

    // @todo Get query data from route variables.
    $term = 'qui';

    try {
      /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
      $backend = $server->getBackend();
      $connector = $backend->getSolrConnector();
      $query = $connector->getSelectQuery();
      $query->setQuery($term);

        // Build a filter.
//    $filter = $query->createFilter('OR');
//    $filter->condition('type', 'article', '=');
//    $filter->condition('type', 'blog_post', '=');
//    $query->filter($filter);

        // Conditions.
//    $query->condition('title_field', $term, '=');
//    $query->condition('language', $language->language, '=');
//    $query->sort('timestamp_field');

      // Fetch results.
      $response = $connector->search($query);
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_federated_solr', $e, '%type while executed query on @server: @message in %function (line %line of %file).', array('@server' => $server->label()));
    }

//    $data['response'] = $results->getExtraData('search_api_solr_response')['response'];
    $data['response'] = json_decode($response->getBody())->response;
      if (true){}

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
