<?php

/**
 * @copyright Copyright (c) 2018 Palantir.net
 */

/**
 * Class SearchApiFederatedSolrRemap
 * Provides a Search API index data alteration that remaps property names for indexed items.
 */
class SearchApiFederatedSolrRemap extends SearchApiAbstractAlterCallback {

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    $properties = [];

    $source_fields = $this->index->getFields(FALSE);

    foreach (array_keys($this->federatedFields()) as $destination_key) {
      if (!empty($this->options['remap'][$destination_key]) && isset($source_fields[$this->options['remap'][$destination_key]])) {
        $source_key = $this->options['remap'][$destination_key];
        $source = $source_fields[$source_key];

        $properties[$destination_key] = [
          'label' => t('@field (remapped from @key)', ['@field' => $source['name'], '@key' => $source_key]),
          'description' => $source['description'],
          'type' => $source['type'],
        ];
      }
    }

    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function alterItems(array &$items) {
    foreach ($items as &$item) {
      foreach ($this->options['fields'] as $key => $value) {
        if ($value && isset($item->{$key})) {
          $item->{$value} = $item->{$key};
          unset($item->{$key});
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    watchdog('debug', 'SearchApiFederatedSolrRemap::configurationForm()');
    //watchdog('debug', 'All fields: <pre>' . print_r($this->index->getFields(FALSE), true) . '</pre>');
    //watchdog('debug', 'federated field options: <pre>' . print_r($this->federatedFieldOptions(), TRUE) . '</pre>');
    //watchdog('debug', 'index field options: <pre>' . print_r($this->indexFieldOptions(), TRUE) . '</pre>');

    $form['remap'] = [
      '#type' => 'fieldset',
      '#title' => t('Remap properties'),
    ];
    foreach ($this->federatedFieldOptions() as $k => $title) {
      $form['remap'][$k] = [
        '#type' => 'select',
        '#title' => $title,
        '#options' => $this->indexFieldOptions(),
        '#default_value' => isset($this->options['remap'][$k]) ? $this->options['remap'][$k] : '',
      ];
    }

    return $form;
  }

  protected function federatedFields() {
    return [
      'federated_title' => [
        'name' => t('Federated Title'),
        'description' => '',
        'type' => 'string'
      ],
      'rendered_output' => [
        'name' => t('Rendered Output'),
        'description' => '',
        'type' => 'text',
      ]
    ];
  }

    protected function federatedFieldOptions() {
      $options = $this->federatedFields();
      array_walk($options, function (&$item, $key) {
        $item = "{$item['name']} ({$key})";
      });
      return $options;
    }

  protected function indexFieldOptions() {
    $options = array_diff_key($this->index->getFields(FALSE), $this->federatedFields());
    array_walk($options, function (&$item, $key) {
      $item = "{$item['name']} ({$key})";
    });
    return ['- ' . t('None') . ' -'] + $options;
  }

}
