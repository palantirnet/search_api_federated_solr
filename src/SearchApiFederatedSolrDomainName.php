<?php

/**
 * @copyright Copyright (c) 2018 Palantir.net
 */

/**
 * Class SearchApiFederatedSolrDomainName
 * Provides a Search API index data alteration that adds the Domain Access domain name to each indexed item.
 */
class SearchApiFederatedSolrDomainName extends SearchApiAbstractAlterCallback {

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
      'domain_name' => array(
        'label' => t('Domain Name'),
        'description' => t('Adds the Domain name to the indexed data.'),
        'type' => 'string',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    $type = $this->index->getEntityType();

    // Map the Domain of each node to its configured label.
    foreach ($items as &$item) {
      $nid = entity_id($type, $item);
      $domain = domain_get_node_match($nid);

      $federated_domain = isset($this->options['domain'][$domain['machine_name']]) ? $this->options['domain'][$domain['machine_name']] : $domain['sitename'];
      $item->domain_name = $federated_domain;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $form['domain'] = ['#type' => 'container'];

    // Provide a configuration field to map each Domain to a different label for indexing.
    foreach (domain_list_by_machine_name() as $machine_name => $domain) {
      $form['domain'][$machine_name] = [
        '#type' => 'textfield',
        '#title' => t('Domain Label'),
        '#description' => t('Map the Domain to a custom label for search.'),
        '#default_value' => !empty($this->options['domain'][$machine_name]) ? $this->options['domain'][$machine_name] : $domain['sitename'],
        '#required' => TRUE,
      ];
    }

    return $form;
  }

}
