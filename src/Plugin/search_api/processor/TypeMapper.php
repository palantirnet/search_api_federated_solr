<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;


/**
 * Links the base url with the site name.
 *
 * @SearchApiProcessor(
 *   id = "type_mapper",
 *   label = @Translation("Type mapper"),
 *   description = @Translation("Rewrites content types before indexing."),
 *   stages = {
 *     "preprocess_index" = 0
 *   }
 * )
 */
class TypeMapper extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#description' => $this->t('Config will be here.'),
    ];

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $datasource_configuration = [];
      if (isset($this->configuration['mappings'][$datasource_id])) {
        $datasource_configuration = $this->configuration['mappings'][$datasource_id];
      }
      $datasource_configuration += [
        'bundle_mappings' => [],
      ];
      $datasource_mapping = $datasource_configuration['datasource_mapping'];
      $bundle_mappings = $datasource_configuration['bundle_mappings'];

      $form['mappings'][$datasource_id] = [
        '#type' => 'details',
        '#title' => $this->t('Mapping settings for %datasource', ['%datasource' => $datasource->label()]),
        '#open' => TRUE,
      ];

      // Add a boost for every available bundle. Drop the "pseudo-bundle" that
      // is added when the datasource does not contain any bundles.
      $bundles = $datasource->getBundles();

      foreach ($bundles as $bundle => $bundle_label) {
        $has_value = isset($bundle_mappings[$bundle]);
        $bundle_mapping = $has_value ? $bundle_mappings[$bundle] : '';
        $form['mappings'][$datasource_id]['bundle_mappings'][$bundle] = [
          '#type' => 'textfield',
          '#title' => $this->t('Mapping for the %bundle bundle', ['%bundle' => $bundle_label]),
          '#default_value' => $bundle_mapping,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($this->index->getDatasourceIds() as $datasource_id) {
      if (!empty($values['mappings'][$datasource_id]['bundle_mappings'])) {
        foreach ($values['mappings'][$datasource_id]['bundle_mappings'] as $bundle => $mapping) {
          if ($mapping === '') {
            unset($values['mappings'][$datasource_id]['bundle_mappings'][$bundle]);
          }
        }
        if (!$values['mappings'][$datasource_id]['bundle_mappings']) {
          unset($values['mappings'][$datasource_id]['bundle_mappings']);
        }
      }
    }
    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    $mappings = $this->configuration['mappings'];

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $datasource_id = $item->getDatasourceId();
      $bundle = $item->getDatasource()->getItemBundle($item->getOriginalObject());

      $type = $item->getField('content_type');
      if ($bundle && isset($mappings[$datasource_id]['bundle_mappings'][$bundle])) {
        $item->setField('content_type', $type->setValues([$mappings[$datasource_id]['bundle_mappings'][$bundle]]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  // public function postprocessSearchResults(ResultSetInterface $results) {
  //   $query = $results->getQuery();
  //   if (!$results->getResultCount()) {
  //     return;
  //   }
  //
  //   $result_items = $results->getResultItems();
  //   foreach ($result_items as $key => $item) {
  //     // $site = $item->getExtraData('search_api_solr_document')['site'];
  //     // $url = Url::fromUri($site);
  //     // $name = $item->getField('site_name')->getValues()[0];
  //     // $link = Link::fromTextAndUrl(t($name), $url)->toString();
  //     // $item->getField('site_name')->setValues([$link]);
  //   }
  // }

}
