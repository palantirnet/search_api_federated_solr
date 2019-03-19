<?php

namespace Drupal\search_api_federated_solr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Request;

class SolrProxyController extends ControllerBase {

  public function getResultsJson(Request $request) {
    $data = [];
    // Do some useful stuff to build an array of data.
    $data['response'] = [];
    $data['response']['docs'] = [];
    $data['response']['docs'][] = 'hi';

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
