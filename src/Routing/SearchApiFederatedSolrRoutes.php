<?php

namespace Drupal\search_api_federated_solr\Routing;

/**
 * @file
 * Contains Drupal\search_api_federated_solr\Routing\SearchApiFederatedSolrRoutes.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new SearchApiRoutes object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
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

    $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');

    $path = $app_config->get('path') ?: '/search-app';

    $args = [
      '_controller' => 'Drupal\search_api_federated_solr\Controller\SearchController::searchPage',
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
