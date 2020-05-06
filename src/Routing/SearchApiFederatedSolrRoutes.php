<?php

namespace Drupal\search_api_federated_solr\Routing;

/**
 * @file
 * Contains Drupal\search_api_federated_solr\Routing\SearchApiFederatedSolrRoutes.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving search pages.
 */
class SearchApiFederatedSolrRoutes implements ContainerInjectionInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new SearchApiRoutes object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service. Currently unused.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    $app_config = $this->configFactory->get('search_api_federated_solr.search_app.settings');

    $path = $app_config->get('path') ?: '/search-app';

    $args = [
      '_controller' => 'Drupal\search_api_federated_solr\Controller\SearchController::content',
      '_title' => 'Search',
    ];

    $routes['search_api_federated_solr.search'] = new Route(
      $path,
      $args,
      [
        '_permission' => 'use federated search',
      ]
    );

    return $routes;
  }

}
