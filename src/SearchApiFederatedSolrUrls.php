<?php

/**
 * Class SearchApiFederatedSolrUrls
 * Provides a Search API index data alteration that adds the sites that the
 * content is available on to each indexed item.
 */
class SearchApiFederatedSolrUrls extends SearchApiAbstractAlterCallback {

  /**
   * @var SearchApiIndex
   */
  protected $index;

  /**
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    return [
      'urls' => [
        'label' => t('URLs'),
        'description' => t('URLs pointing to this node on all sites containing'),
        'type' => 'list<uri>',
        'cardinality' => -1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {

    if ($this->useDomainAccess()) {
      $this->addDomainUrls($items);
    }
    else {
      $this->addUrl($items);
    }

  }

  protected function addUrl(array &$items) {
    foreach ($items as &$item) {
      $url = $this->index->datasource()->getItemUrl($item);
      if (!$url) {
        $item->search_api_urls = NULL;
        continue;
      }
      $item->search_api_urls = [url($url['path'], array('absolute' => TRUE) + $url['options'])];
    }
  }

  protected function addDomainUrls(array &$items) {
    $entity_type = $this->index->getEntityType();
    $entity_info = entity_get_info($entity_type);

    foreach ($items as $item) {
      $id = entity_id($entity_type, $item);

      // Get the entity object for the item being indexed, exit if there's somehow not one.
      $entity = current(entity_load($entity_type, [$id]));
      if (!$entity) {
        return;
      }

      $urls = domain_get_content_urls($entity);

      $item->urls = $urls;
    }

  }

  /**
   * Whether to use the site name from Domain Access.
   *
   * @return bool
   */
  protected function useDomainAccess() {
    return function_exists('domain_get_content_urls');
  }

}
