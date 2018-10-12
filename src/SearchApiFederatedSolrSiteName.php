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
  public function supportsIndex(SearchApiIndex $index) {
    // Code in this class assumes that it is working with nodes.
    return $index->getEntityType() == 'node';
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    return array(
      'site_name' => array(
        'label' => t('Site Name'),
        'description' => t('Adds the site name to the indexed data.'),
        'type' => 'list<string>',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    if ($this->useDomainAccess()) {
      $this->addDomainName($items);
    }
    else {
      $this->addSiteName($items);
    }
  }

  protected function addSiteName(array &$items) {
    $site_name = !empty($this->options['site_name']) ? $this->options['site_name'] : variable_get('site_name');

    foreach ($items as &$item) {
      $item->site_name = [$site_name];
    }
  }

  protected function addDomainName(array &$items) {
    $type = $this->index->getEntityType();

    // Map the Domain of each node to its configured label.
    foreach ($items as &$item) {
      $nid = entity_id($type, $item);
      $entity = current(entity_load($type, [$nid]));
      $domains = domain_get_content_urls($entity);

      $ds = [];
      foreach ($domains as $domain_id => $url ) {
        $domain = domain_lookup($domain_id);
        $ds[] = !empty($this->options['domain'][$domain['machine_name']]) ? $this->options['domain'][$domain['machine_name']] : $domain['sitename'];
      }
      
      $item->site_name = $ds;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    if ($this->useDomainAccess()) {
      $form['domain'] = ['#type' => 'container'];

      // Provide a configuration field to map each Domain to a different label for indexing.
      foreach (domain_list_by_machine_name() as $machine_name => $domain) {
        $form['domain'][$machine_name] = [
          '#type' => 'textfield',
          '#title' => t('%domain Domain Label', ['%domain' => $domain['sitename']]),
          '#description' => t('Map the Domain to a custom label for search.'),
          '#default_value' => !empty($this->options['domain'][$machine_name]) ? $this->options['domain'][$machine_name] : $domain['sitename'],
          '#required' => TRUE,
        ];
      }
    }
    else {
      $form['site_name'] = [
        '#type' => 'textfield',
        '#title' => t('Site Name'),
        '#description' => t('The name of the site from which this content originated. This can be useful if indexing multiple sites with a single search index.'),
        '#default_value' => !empty($this->options['site_name']) ? $this->options['site_name'] : variable_get('site_name'),
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Whether to use the site name from Domain Access.
   *
   * @return bool
   */
  protected function useDomainAccess() {
    return function_exists('domain_list_by_machine_name');
  }

}
