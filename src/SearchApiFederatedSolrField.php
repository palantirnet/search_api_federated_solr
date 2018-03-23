<?php

class SearchApiFederatedSolrField extends SearchApiAbstractAlterCallback {

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
  public function alterItems(array &$items) {
    if (empty($this->options['fields'])) { return; }

    $entity_type = $this->index->getEntityType();
    $entity_info = entity_get_info($entity_type);

    foreach ($items as &$item) {
      $id = entity_id($entity_type, $item);
      $entity = current(entity_load($entity_type, [$id]));

      $bundle = $entity->{$entity_info['entity keys']['bundle']};
      foreach ($this->options['fields'] as $field) {
        if (isset($field['bundle'][$bundle])) {
          $item->{$field['machine_name']} = token_replace($field['bundle'][$bundle], [$entity_type => $entity]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm() {
    $form['fields'] = [
      '#type' => 'container',
      '#prefix' => '<div id="search-api-federated-solr-add-field-settings">',
      '#suffix' => '</div>',
    ];

    foreach ($this->options['fields'] as $key => $field) {
      $item = [
        '#type' => 'fieldset',
        '#title' => !isset($field['is_new']) ?
          t("@label (%machine_name)", ['@label' => $field['label'], '%machine_name' => $field['machine_name']]) : t('New field'),
        '#collapsible' => TRUE,
        '#collapsed' => !isset($field['is_new']),
      ];
      $item['label'] = [
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#default_value' => $field['label'],
        '#maxlength' => 255,
      ];
      $item['machine_name'] = [
        '#type' => 'textfield',
        '#title' => t('Machine Name'),
        '#required' => TRUE,
        '#default_value' => $field['machine_name'],
        '#maxlength' => 32,
      ];
      $item['type'] = [
        '#type' => 'select',
        '#title' => t('Data type'),
        '#options' => search_api_default_field_types(),
        '#default_value' => (TRUE === isset($field['type'])) ? $field['type'] : 'string',
        '#required' => TRUE,
        '#description' => t('Data type to save field as'),
      ];
      $item['bundle'] = [
        '#type' => 'fieldset',
        '#title' => t('Value to index for each type'),
        '#description' => t('Enter a token or plain text in the field for each type of indexed item.'),
        '#collapsible' => TRUE,
      ];

      $entity_info = entity_get_info($this->index->getEntityType());
      foreach ($entity_info['bundles'] as $bundle => $bundle_info) {
        $item['bundle'][$bundle] = [
          '#type' => 'textfield',
          '#title' => t('@label (%machine_name)', ['@label' => $bundle_info['label'], '%machine_name' => $bundle]),
          '#default_value' => $field['bundle'][$bundle],
        ];
      }

      $item['actions'] = array(
        '#type' => 'actions',
        'remove' => array(
          '#type' => 'submit',
          '#value' => t('Remove field'),
          '#submit' => array('_search_api_federated_solr_add_field_submit'),
          '#limit_validation_errors' => array(),
          '#name' => '_search_api_federated_solr_remove_' . $key,
          '#ajax' => array(
            'callback' => '_search_api_federated_solr_add_field_ajax',
            'wrapper' => 'search-api-federated-solr-add-field-settings',
          ),
        ),
      );


      $form['fields'][$key] = $item;
    }

    $form['tokens'] = [
      '#type' => 'fieldset',
      '#title' => t('Tokens'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['tokens']['tokens'] = [
      '#theme' => 'token_tree',
      '#token_types' => [$this->index->getEntityType()],
      '#global_types' => FALSE,
      '#recursion_limit' => 2,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['add_field_federated'] = array(
      '#type' => 'submit',
      '#value' => t('Add new federated field'),
      '#submit' => array('_search_api_federated_solr_add_field_submit'),
      '#limit_validation_errors' => array(),
      '#ajax' => array(
        'callback' => '_search_api_federated_solr_add_field_ajax',
        'wrapper' => 'search-api-federated-solr-add-field-settings',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate(array $form, array &$values, array &$form_state) {
    parent::configurationFormValidate($form, $values, $form_state);

    foreach ($values['fields'] as $key => $field) {
      if (preg_match('/^[0-9]|[^a-z0-9_]/i', $field['machine_name'])) {
        $name = "callbacks][federated_field][settings][fields][{$key}][machine_name";
        form_set_error($name, 'Federated field machine names must consist of alphanumeric or underscore characters only and not start with a digit.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, array &$values, array &$form_state) {
    foreach ($values['fields'] as $key => $field) {
      if ($key != $field['machine_name']) {
        unset($values['fields'][$key]);
        $values['fields'][$field['machine_name']] = $field;
      }
    }

    $this->options = $values;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo() {
    $properties = [];

    foreach ($this->options['fields'] as $field) {
      $properties[$field['machine_name']] = [
        'label' => $field['label'],
        'description' => t('Federated field.'),
        'type' => $field['type'],
      ];
    }

    return $properties;
  }

  /**
   * Submit helper callback for buttons in the callback's configuration form.
   */
  public function formButtonSubmit(array $form, array &$form_state) {
    $button_name = $form_state['triggering_element']['#name'];
    if ($button_name == 'op') {
      $this->options['fields']['_new_field_' . REQUEST_TIME] = ['is_new' => TRUE];
    }
    else {
      $key = substr($button_name, 34);
      unset($this->options['fields'][$key]);
    }
    $form_state['rebuild'] = TRUE;
    $this->changes = TRUE;
  }

}

/**
 * Submit function for buttons in the callback's configuration form.
 */
function _search_api_federated_solr_add_field_submit(array $form, array &$form_state) {
  $form_state['callbacks']['federated_field']->formButtonSubmit($form, $form_state);
}

/**
 * AJAX submit function for buttons in the callback's configuration form.
 */
function _search_api_federated_solr_add_field_ajax(array $form, array &$form_state) {
  return $form['callbacks']['settings']['federated_field']['fields'];
}
