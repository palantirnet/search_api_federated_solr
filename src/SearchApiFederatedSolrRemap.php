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

    $fields = $this->index->getFields();

    foreach ($this->options['fields'] as $key => $value) {
      if ($value && isset($fields[$key])) {
        $properties[$value] = [
          'label' => t('@field (remapped from @key)', ['@field' => $fields[$key]['name'], '@key' => $key]),
          'description' => $fields[$key]['description'],
          'type' => $fields[$key]['type'],
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

    $form['fields'] = [
      '#type' => 'fieldset',
      '#title' => t('Remap properties'),
      '#description' => t('Enter a machine name to use instead of the original. Blank fields will not be remapped.'),
    ];

    $fields = $this->index->getFields();
    foreach ($fields as $key => $field) {
      $form['fields'][$key] = [
        '#type' => 'textfield',
        '#title' => t('@name (%machine_name)', ['@name' => $field['name'], '%machine_name' => $key]),
        '#default_value' => isset($this->options['fields'][$key]) ? $this->options['fields'][$key] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate(array $form, array &$values, array &$form_state) {
    parent::configurationFormValidate($form, $values, $form_state);

    foreach ($values['fields'] as $key => $value) {
      if (preg_match('/^[0-9]|[^a-z0-9_]/i', $value)) {
        $name = "callbacks][remap][settings][fields][{$key}";
        form_set_error($name, 'Remapped field machine names must consist of alphanumeric or underscore characters only and not start with a digit.');
      }
    }
  }

}
