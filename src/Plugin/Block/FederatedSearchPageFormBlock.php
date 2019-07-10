<?php

namespace Drupal\search_api_federated_solr\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api_federated_solr\Utility\Helpers;
use Drupal\Component\Utility\UrlHelper;

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
    $config = $this->getConfiguration();
    $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');

    $build = [
      '#theme' => 'search_api_federated_solr_block',
      '#search_form' => \Drupal::formBuilder()->getForm('Drupal\search_api_federated_solr\Form\FederatedSearchPageBlockForm'),
    ];


    // If autocomplete is enabled for this block, attach the js library.
    if (array_key_exists('autocomplete',$config)
        && array_key_exists('isEnabled', $config['autocomplete'])
        && $config['autocomplete']['isEnabled'] === 1) {
      // Attach autocomplete JS library.
      $build['#attached']['library'][] = 'search_api_federated_solr/search_form_autocomplete';
      // Cache autocomplete config.
      $autocomplete = $config['autocomplete'];
      $proxy_is_disabled = array_key_exists('proxyIsDisabled', $autocomplete) ? $autocomplete['proxyIsDisabled'] : 0;
      $direct_url = array_key_exists('directUrl', $autocomplete) ? $autocomplete['directUrl'] : '';

      // Determine the url that should be used for autocomplete.
      $autocomplete['url'] = Helpers::getEndpointUrl($proxy_is_disabled, $direct_url, '?q=[val]&wt=json');

      // Set the connstraints for allowed sites.
      if ($allowed_sites = $app_config->get('facet.site_name.allowed_sites')) {
        $list = [];
        $sites = array_keys(array_filter($allowed_sites));
        foreach ($sites as $site) {
          $list[] = '"' . $site . '"';
        }
        $autocomplete['sm_site_name'] = '(' . implode(' OR ', $list) . ')';
      }

      // Write the block autocomplete config to Drupal settings.
      $build['#attached']['drupalSettings']['searchApiFederatedSolr'] = [
        'block' => [
          'autocomplete' => $autocomplete,
        ],
      ];
      // Add the js trigger class to the block.
      $build['#attributes']['class'][] = 'js-search-api-federated-solr-block-form-autocomplete';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Create a link to the Search App Settings Page to be reused.
    $options = [
      'attributes' => [
        'target' => '_blank'
      ],
    ];
    $search_app_settings_page_url = Url::fromRoute('search_api_federated_solr.search_app.settings', [], $options);
    $search_app_settings_page_link = Link::fromTextAndUrl('Federated Search App settings page', $search_app_settings_page_url)->toString();

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
      '#description' => $this->t('These options apply to the autocomplete functionality on the search form which appears on pages that render the Federated Search Page Form Block.  Configure the search results page search form functionality on the ' . $search_app_settings_page_link  . '.'),
      '#open' => TRUE,
    ];

    $form['autocomplete']['autocomplete_is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Enable autocomplete for the search results page search form') . '</b>',
      '#default_value' => isset($config['autocomplete']['isEnabled']) ? $config['autocomplete']['isEnabled'] : 0,
      '#description' => $this
        ->t('Check this box to enable autocomplete on the federated search block search form and to expose more configuration options below.'),
      '#attributes' => [
        'data-autocomplete-enable' => TRUE,
      ],
    ];

    $form['autocomplete']['autocomplete_is_append_wildcard'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Append a wildcard \'*\' to support partial text search') . '</b>',
      '#default_value' => isset($config['autocomplete']['appendWildcard']) ? $config['autocomplete']['appendWildcard'] : 0,
      '#description' => $this
        ->t('Check this box to append a wildcard * to the end of the autocomplete query term (i.e. "car" becomes "car*").  This option is only recommended if your solr config does not add a field(s) with <a href="https://lucene.apache.org/solr/guide/6_6/tokenizers.html" target="_blank">NGram Tokenizers</a> to your index or if your <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> is not configured to search those fields.'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['disable_query_proxy'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Do not use the solr proxy for the autocomplete search query') . '</b>',
      '#default_value' => isset($config['autocomplete']['proxyIsDisabled']) ? $config['autocomplete']['proxyIsDisabled'] : 0,
      '#description' => $this
        ->t('Check this box to configure the block search form to query the Solr server directly. When checked, it is highly recommended that you also procure and configure read-only basic auth credentials for the search app. When unchecked, this site will act as a proxy for requests to the Solr server of the Search API index chosen on the ' . $search_app_settings_page_link  . ' in Search Results Page > Set Up using the Drupal route defined by this module.<br/><br/>Note: Acquia Search customers must either leave this box unchecked or check the box and enter the URL for a view REST export endpoint.  Using a url pointing directly to your solr backend will not work.'),
      '#attributes' => [
        'data-autocomplete-direct' => TRUE,
      ],
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Direct Query Settings'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
          ':input[data-autocomplete-direct]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['autocomplete_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => isset($config['autocomplete']['directUrl']) ? $config['autocomplete']['directUrl'] : '',
      '#maxlength' => 2048,
      '#size' => 50,
      '#description' => $this
        ->t('The URL where requests for autocomplete queries should be made. (Default: the url of the  <code>select</code> <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> on the server of the selected Search API index.)<ul><li>Supports absolute url pattern to any endpoint which returns the expected response structure:
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
          ':input[data-autocomplete-direct]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Form Autocomplete Basic Authentication'),
      '#description' => $this->t('If your autocomplete endpoint is protected by basic HTTP authentication (highly recommended), enter the login data here. This will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-direct]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['use_search_app_creds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use credentials provided for Search Index Basic Authentication in Search Results Page > Set Up on ' . $search_app_settings_page_link),
      '#default_value' => isset($config['autocomplete']['use_search_app_creds']) ? $config['autocomplete']['use_search_app_creds'] : 0,
      '#attributes' => [
        'data-autocomplete-use-search-app-creds' => TRUE,
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => isset($config['autocomplete']['username']) ? $config['autocomplete']['username'] : '',
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-use-search-app-creds]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => isset($config['autocomplete']['password']) ? $config['autocomplete']['password'] : '',
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-use-search-app-creds]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_suggestion_rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => isset($config['autocomplete']['suggestionRows']) ? $config['autocomplete']['suggestionRows'] : '',
      '#description' => $this
        ->t('The max number of results to render in the autocomplete results dropdown. (Default: 5)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_num_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters after which autocomplete query should execute'),
      '#default_value' => isset($config['autocomplete']['numChars']) ? $config['autocomplete']['numChars'] : '',
      '#description' => $this
        ->t('Autocomplete query will be executed <em>after</em> a user types this many characters in the search query field. (Default: 2)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $autocomplete_mode = isset($config['autocomplete']['mode']) ? $config['autocomplete']['mode'] : '';

    $form['autocomplete']['autocomplete_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocomplete mode'),
      '#description' => $this->t('Type of results the autocomplete response returns: search results (default) or search terms.'),
      '#options' => [
        'result' => $this
          ->t('Search results (i.e. search as you type functionality)'),
        'Search terms (coming soon)' => [],
      ],
      '#default_value' => isset($config['autocomplete']['mode']) ? $config['autocomplete']['mode'] : 'result',
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results title text'),
      '#size' => 50,
      '#default_value' => $autocomplete_mode && isset($config['autocomplete'][$autocomplete_mode]['titleText']) ? $config['autocomplete'][$autocomplete_mode]['titleText'] : '',
      '#description' => $this
        ->t('The title text is shown above the results in the autocomplete drop down.  (Default: "What are you interested in?" for Search Results mode and "What would you like to search for?" for Search Term mode.)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_hide_directions'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Hide keyboard directions') . '</b>',
      '#default_value' => $autocomplete_mode && isset($config['autocomplete'][$autocomplete_mode]['hideDirectionsText']) ? $config['autocomplete'][$autocomplete_mode]['hideDirectionsText'] : 0,
      '#description' => $this
        ->t('Check this box to make hide the autocomplete keyboard usage directions in the results dropdown. For sites that want to maximize their accessibility UX for sighted keyboard users, we recommend leaving this unchecked. (Default: directions are visible)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enable]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Validates that the provided autocomplete endpoint path is a valid relative or
   * absolute URL.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $key = ['autocomplete', 'direct', 'autocomplete_url'];
    $path = $form_state->getValue($key);
    $proxy_is_disabled = $form_state->getValue(['autocomplete', 'disable_query_proxy']);
    if ($path && $proxy_is_disabled) {
      if ($path !== '' && !UrlHelper::isValid($path, FALSE)) {
        $element['#parents'] = $key;
        $form_state
          ->setError($element, t('The URL %url is not valid.', array(
          '%url' => $path,
        )));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    // Define return object.
    $autocomplete = [];
    // Set autocomplete options.
    $autocomplete_is_enabled = $values['autocomplete']['autocomplete_is_enabled'];
    // If enabled, set the autocomplete options.
    if ($autocomplete_is_enabled) {
      // Cache form values that we'll use more than once.
      $autocomplete_mode = $values['autocomplete']['autocomplete_mode'];

      // Default to the form values
      $username = $values['autocomplete']['direct']['basic_auth']['username'];
      $password = $values['autocomplete']['direct']['basic_auth']['password'];
      $use_search_app_creds = $values['autocomplete']['direct']['basic_auth']['use_search_app_creds'];
      // Add basic auth credentials
      if ($use_search_app_creds) {
        $app_config = \Drupal::config('search_api_federated_solr.search_app.settings');
        $username = $app_config->get('index.username');
        $password = $app_config->get('index.password');
      }

      // Set the actual autocomplete config options.
      $autocomplete['isEnabled'] = $autocomplete_is_enabled;
      $autocomplete['proxyIsDisabled'] = $values['autocomplete']['disable_query_proxy'];
      $autocomplete['directUrl'] = $autocomplete['proxyIsDisabled'] ? $values['autocomplete']['direct']['autocomplete_url'] : '';
      $autocomplete['use_search_app_creds'] = $use_search_app_creds;

      if ($username) {
        $autocomplete['username'] = $username;
      }
      if ($password) {
        $autocomplete['password'] = $password;
      }
      if ($username && $password) {
        $autocomplete['userpass'] = base64_encode($username . ':' . $password);
      }
      if ($values['autocomplete']['autocomplete_is_append_wildcard']) {
        $autocomplete['appendWildcard'] = $values['autocomplete']['autocomplete_is_append_wildcard'];
      }
      if ($values['autocomplete']['autocomplete_suggestion_rows']) {
        $autocomplete['suggestionRows'] = $values['autocomplete']['autocomplete_suggestion_rows'];
      }
      if ($values['autocomplete']['autocomplete_num_chars']) {
        $autocomplete['numChars'] = $values['autocomplete']['autocomplete_num_chars'];
      }
      if ($autocomplete_mode) {
        $autocomplete['mode'] = $autocomplete_mode;
        if ($values['autocomplete']['autocomplete_mode_title_text']) {
          $autocomplete[$autocomplete_mode]['titleText'] = $values['autocomplete']['autocomplete_mode_title_text'];
        }
        if ($values['autocomplete']['autocomplete_mode_hide_directions']) {
          $autocomplete[$autocomplete_mode]['hideDirectionsText'] = $values['autocomplete']['autocomplete_mode_hide_directions'];
        }
      }
    }

    $this->configuration['autocomplete'] = $autocomplete;
  }
}
