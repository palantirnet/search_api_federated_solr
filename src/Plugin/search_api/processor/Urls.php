<?php

namespace Drupal\search_api_federated_solr\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_federated_solr\Plugin\search_api\processor\Property\URLsProperty;


/**
 * Adds the Urls to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_urls",
 *   label = @Translation("Urls"),
 *   description = @Translation("Adds the Urls to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Urls extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Urls'),
        'description' => $this->t('URLs pointing to this node on all sites containing.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_urls'] = new URLsProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
    if ($url) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'search_api_urls');
      foreach ($fields as $field) {
        $url = $url->setAbsolute()->toString();
        $field->addValue($url);
      }
    }
  }
}
