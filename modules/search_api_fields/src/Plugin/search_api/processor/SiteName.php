<?php

namespace Drupal\search_api_fields\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api_fields\Plugin\search_api\processor\Property\SiteNameProperty;


/**
 * Adds the site name to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "site_name",
 *   label = @Translation("Site name"),
 *   description = @Translation("Adds the site name to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SiteName extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Site Name'),
        'description' => $this->t('The name of the site from which this content originated.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['site_name'] = new SiteNameProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'site_name');
    foreach ($fields as $field) {
      $site_name = $field->getConfiguration()['site_name'];
      $field->addValue($site_name);
    }
  }

}
