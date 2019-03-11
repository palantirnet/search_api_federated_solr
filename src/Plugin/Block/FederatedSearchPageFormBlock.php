<?php

namespace Drupal\search_api_federated_solr\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Federated Search Page Form" block.
 *
 * @Block(
 *   id = "federated_search_page_form_block",
 *   admin_label = @Translation("Federated Search Page Form block"),
 *   category = @Translation("Federated Search"),
 * )
 */
class FederatedSearchPageFormBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $build['container']['form'] = \Drupal::formBuilder()->getForm('Drupal\search_api_federated_solr\Form\FederatedSearchPageBlockForm');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $index_options = [];
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    /**
     * Autocomplete settings:
     *   - endpoint URL
     *   - use wildcard to support partial terms
     *   - customize number of autocomplete results
     *   - number of characters after which autocomplete query should be executed
     *   - autocomplete results mode (search results, terms)
     *   - title for autocomplete results
     *   - show/hide autocomplete keyboard directions
     */

    $form['autocomplete'] = [
      '#type' => 'details',
      '#title' => $this->t('Federated Search Page Form Block > Search Form > Autocomplete'),
      '#description' => $this->t('These options apply to the autocomplete functionality on the search form which appears on pages that render the Federated Search Page Form Block.  Configure the search results page search form functionality on the Federated Search App settings page.'),
      '#open' => TRUE,
    ];

    $form['autocomplete']['autocomplete_is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Enable autocomplete for the search results page search form') . '</b>',
      '#default_value' => $config['autocomplete']['isEnabled'],
      '#description' => $this
        ->t('Check this box to enable autocomplete for the text input on the search form rendered in this block.'),
      '#attributes' => [
        'id' => ['autocomplete-is-enabled'],
      ],
    ];

    $form['autocomplete']['autocomplete_is_append_wildcard'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Append a wildcard \'*\' to support partial text search') . '</b>',
      '#default_value' => $config['autocomplete']['appendWildcard'],
      '#description' => $this
        ->t('Check this box to append a wildcard * to the end of the autocomplete query term (i.e. "car" becomes "car+car*").  This option is recommended if your solr config does not add a field(s) with <a href="https://lucene.apache.org/solr/guide/6_6/tokenizers.html" target="_blank">NGram Tokenizers</a> to your index or if your autocomplete <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> is not configured to search those fields.'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $config['autocomplete']['url'],
      '#maxlength' => 2048,
      '#size' => 50,
      '#description' => $this
        ->t('The URL where requests for autocomplete queries should be made. (Default: the url of the  <code>select</code> Request Handler on the server of the selected Search API index.)<ul><li>Supports absolute url pattern to any endpoint which returns the expected response structure:
<pre><code>{
  response: {
   docs: [
     {
       ss_federated_title: [result title to be used as link text],
       ss_url: [result url to be used as link href],
     }
   ]
  }
}</code></pre></li><li>Include <code>[val]</code> in the URL to indicate where you would like the form value to be inserted: <code>http://d8.fs-demo.local/search-api-federated-solr-block-form-autocomplete/search-view?title=[val]&_format=json</code></li><li>Any facet/filter default values set for the search app will automatically be appended (i.e. <code>&sm_site_name=[value of the site name for the index]</code>)</li><li>Include any other necessary url params (like <code>&_format=json</code> if you are using a Views Rest Export or <code>&wt=json</code> if you are using a different Request Handler on your Solr index.</li>'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_suggestion_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $config['autocomplete']['suggestionRows'],
      '#description' => $this
        ->t('The max number of results to render in the autocomplete results dropdown. (Default: 5)'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_num_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters after which autocomplete query should execute'),
      '#default_value' => $config['autocomplete']['numChars'],
      '#description' => $this
        ->t('Autocomplete query will be executed <em>after</em> a user types this many characters in the search query field. (Default: 2)'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $autocomplete_mode = $config['autocomplete']['mode'];

    $form['autocomplete']['autocomplete_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Result mode'),
      '#description' => $this->t('Type of results the autocomplete response returns: search results (default) or search terms.'),
      '#options' => [
        'result' => $this
          ->t('Search results (i.e. search as you type functionality)'),
        'Search terms (coming soon)' => [],
      ],
      '#default_value' => $config['autocomplete']['mode'] || 'result',
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results title text'),
      '#size' => 50,
      '#default_value' => $autocomplete_mode ? $config['autocomplete'][$autocomplete_mode]['titleText'] : '',
      '#description' => $this
        ->t('The title text is shown above the results in the autocomplete drop down.  (Default: "What are you interested in?" for Search Results mode and "What would you like to search for?" for Search Term mode.)'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_hide_directions'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Hide keyboard directions') . '</b>',
      '#default_value' => $autocomplete_mode ? $config['autocomplete'][$autocomplete_mode]['hideDirectionsText'] : 0,
      '#description' => $this
        ->t('Check this box to make hide the autocomplete keyboard usage directions in the results dropdown. For sites that want to maximize their accessibility UX for sighted keyboard users, we recommend leaving this unchecked. (Default: directions are visible)'),
      '#states' => [
        'visible' => [
          ':input[id="autocomplete-is-enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    // Set autocomplete options.
    $autocomplete_is_enabled = $values['autocomplete']['autocomplete_is_enabled'];
    $this->configuration['autocomplete']['isEnabled'] = $autocomplete_is_enabled;

    // If enabled, set the autocomplete options.
    if ($autocomplete_is_enabled) {
      // Cache form values that we'll use more than once.
      $autocomplete_url_value = $values['autocomplete']['autocomplete_url'];
      $autocomplete_mode = $values['autocomplete']['autocomplete_mode'];

      // Set the default autocomplete endpoint url to the default search url if none was passed in.
      // Get the id of the chosen index's server.
      $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');
      $search_index = $app_config->get('index.id');
      $index_config = \Drupal::config('search_api.index.' . $search_index);
      $index_server = $index_config->get('server');

      // Get the server url.
      $server_config = \Drupal::config('search_api.server.' . $index_server);
      $server = $server_config->get('backend_config.connector_config');
      // Get the required server config field data.
      $server_url = $server['scheme'] . '://' . $server['host'] . ':' . $server['port'];
      // Check for the non-required server config field data before appending.
      $server_url .= $server['path'] ?: '';
      $server_url .= $server['core'] ? '/' . $server['core'] : '';
      // Append the request handler, main query and format params.
      $server_url .= '/select?q=[val]&wt=json';
      $autocomplete_url = $autocomplete_url_value ? $autocomplete_url_value : $server_url;

      // Set the actual autocomplete config options.
      $this->configuration['autocomplete']['url'] = $autocomplete_url;
      $this->configuration['autocomplete']['appendWildcard'] = $values['autocomplete']['autocomplete_is_append_wildcard'];
      $this->configuration['autocomplete']['suggestionRows'] = $values['autocomplete']['autocomplete_suggestion_rows'];
      $this->configuration['autocomplete']['numChars'] = $values['autocomplete']['autocomplete_num_chars'];
      if ($autocomplete_mode) {
        $this->configuration['autocomplete']['mode'] = $autocomplete_mode;
        $this->configuration['autocomplete'][$autocomplete_mode]['titleText'] = $values['autocomplete']['autocomplete_mode_title_text'];
        $this->configuration['autocomplete'][$autocomplete_mode]['hideDirectionsText'] = $values['autocomplete']['autocomplete_mode_hide_directions'];
      }
    }
  }

}
