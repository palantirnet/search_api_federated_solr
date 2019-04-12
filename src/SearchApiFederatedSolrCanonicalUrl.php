<?php

/**
 * Class SearchApiFederatedSolrCanonicalUrl
 * Provides a Search API index data alteration that indicates the preferred
 * URL content is available on to each indexed item.
 */
class SearchApiFederatedSolrCanonicalUrl extends SearchApiAbstractAlterCallback {

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
      'canonical_url' => [
        'label' => t('Canonical URL'),
        'description' => t('Preferred URL for this content'),
        'type' => 'uri',
        'cardinality' => -1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {

    if ($this->useDomainAccess()) {
      $this->addDomainUrl($items);
    }
    else {
      $this->addUrl($items);
    }

  }

  protected function addUrl(array &$items) {
    foreach ($items as &$item) {
      $url = $this->index->datasource()->getItemUrl($item);
      if (!$url) {
        $item->canonical_url = NULL;
        continue;
      }
      $item->canonical_url = url($url['path'], array('absolute' => TRUE) + $url['options']);
    }
  }

  protected function addDomainUrl(array &$items) {
    $entity_type = $this->index->getEntityType();
    $entity_info = entity_get_info($entity_type);

    foreach ($items as $item) {
      $id = entity_id($entity_type, $item);

      // Get the entity object for the item being indexed, exit if there's somehow not one.
      $entity = current(entity_load($entity_type, [$id]));
      if (!$entity) {
        return;
      }

      $url = domain_get_canonical_url($entity);

      $item->canonical_url = $url;
    }

  }

  /**
   * Whether to use the site name from Domain Access.
   *
   * @return bool
   */
  protected function useDomainAccess() {
    return function_exists('domain_get_canonical_url');
  }

}
