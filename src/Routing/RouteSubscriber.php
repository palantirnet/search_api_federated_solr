<?php
namespace Drupal\search_api_federated_solr\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Allow anonymous access to search_api_federated_solr/settings resource.
    if ($route = $collection->get('rest.search_app_settings_resource.GET')) {
      $requirements = $route->getRequirements();
      if (!empty($requirements['_csrf_request_header_token'])) {
        unset($requirements['_csrf_request_header_token']);
        unset($requirements['_permission']);
        $options = $route->getOptions();
        unset($options['_auth']);
        $route->setOptions($options);
        $route->setRequirements($requirements);
      }
    }
  }

}
