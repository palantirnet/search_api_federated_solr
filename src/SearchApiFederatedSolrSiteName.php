<?php

/**
 * @copyright Copyright (c) 2018 Palantir.net
 */

/**
 * Class SearchApiFederatedSolrSiteName
 * Provides a Search API index data alteration that adds a "Site Name" property to each indexed item.
 */
class SearchApiFederatedSolrSiteName extends SearchApiAbstractAlterCallback {

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    return array(
      'site_name' => array(
        'label' => t('Site Name'),
        'description' => t('Adds the site name to the indexed data.'),
        'type' => 'string',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    $site_name = !empty($this->options['site_name']) ? $this->options['site_name'] : variable_get('site_name');

    foreach ($items as &$item) {
      $item->site_name = $site_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site Name'),
      '#description' => t('The name of the site from which this content originated. This can be useful if indexing multiple sites with a single search index.'),
      '#default_value' => !empty($this->options['site_name']) ? $this->options['site_name'] : variable_get('site_name'),
      '#required' => TRUE,
    ];
    return $form;
  }

}
