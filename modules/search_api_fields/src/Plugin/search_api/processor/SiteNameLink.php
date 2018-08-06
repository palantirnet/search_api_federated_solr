<?php

namespace Drupal\search_api_fields\Plugin\search_api\processor;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Links the base url with the site name.
 *
 * @SearchApiProcessor(
 *   id = "site_name_link",
 *   label = @Translation("Site name link"),
 *   description = @Translation("Links the base url with the site name."),
 *   stages = {
 *     "postprocess_query" = 0
 *   }
 * )
 */
class SiteNameLink extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#description' => $this->t('This processor provides no configuration options.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    $query = $results->getQuery();
    if (!$results->getResultCount()) {
      return;
    }

    $result_items = $results->getResultItems();
    foreach ($result_items as $key => $item) {
      $site = $item->getExtraData('search_api_solr_document')['site'];
      $url = Url::fromUri($site);
      $name = $item->getField('site_name')->getValues()[0];
      $link = Link::fromTextAndUrl(t($name), $url)->toString();
      $item->getField('site_name')->setValues([$link]);
    }
  }

}
