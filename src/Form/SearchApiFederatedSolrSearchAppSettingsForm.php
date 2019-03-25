<?php

namespace Drupal\search_api_federated_solr\Form;

/**
 * @file
 * Contains \Drupal\search_api_solr_federated\Form\SearchApiFederatedSolrSearchAppSettingsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SearchApiFederatedSolrSearchAppSettingsForm.
 *
 * @package Drupal\search_api_federated_solr\Form
 */
class SearchApiFederatedSolrSearchAppSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_federated_solr_search_app_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'search_api_federated_solr.search_app.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#validate'][] = [$this, 'formValidationPathValidate'];

    $config = $this->config('search_api_federated_solr.search_app.settings');

    $index_options = [];
    $search_api_indexes = \Drupal::entityTypeManager()->getStorage('search_api_index')->loadMultiple();
    /* @var  $search_api_index \Drupal\search_api\IndexInterface */
    foreach ($search_api_indexes as $search_api_index) {
      $index_options[$search_api_index->id()] = $search_api_index->label();
    }

    /**
     * Set index config values to indicate which properties
     */
    $site_name_property_value = '';
    $site_name_property_default_value = '';
    // Validates whether or not the search app's chosen index has a site_name,
    // federated_date, federated_type, and federated_terms properties
    // and alters the search app settings form accordingly.
    if ($search_index_id = $config->get('index.id')) {
      $index_config = \Drupal::config('search_api.index.' . $search_index_id);
      // Determine if the index has a site name property, which could have been
      // added / removed since last form load.
      $site_name_property = $index_config->get('field_settings.site_name.configuration.site_name');
      $config->set('index.has_site_name_property', $site_name_property ? TRUE : FALSE);

      // If the index does have a site name property, ensure the hidden form field reflects that.
      if ($site_name_property) {
        $site_name_property_value = 'true';
        $site_name_property_default_value = 'true';
      }
      else {
        // Assume properties are not present, set defaults.
        $site_name_property_value = '';
        $site_name_property_default_value = FALSE;
        $config->set('facet.site_name.set_default', FALSE);
      }

      // Save config indicating which index field properties that
      // correspond to facets and filters are present on the index.
      $type_property = $index_config->get('field_settings.federated_type');
      $config->set('index.has_federated_type_property', $type_property ? TRUE : FALSE);

      $date_property = $index_config->get('field_settings.federated_date');
      $config->set('index.has_federated_date_property', $date_property ? TRUE : FALSE);

      $terms_property = $index_config->get('field_settings.federated_terms');
      $config->set('index.has_federated_terms_property', $terms_property ? TRUE : FALSE);

      $config->save();
    }

    /**
     * Basic set up:
     *   - search results page path
     *   - search results page title
     *   - autocomplete enable triggers display of autocopmlete config fieldset
     *   - search index to use as datasource,
     *   - disable the query proxy
     *   - basic auth credentials for index (if proxy disabled)
     */

    $form['setup'] = [
      '#type' => 'details',
      '#title' => 'Search Results Page > Set Up',
      '#open' => TRUE,
    ];

    $form['setup']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search results page path'),
      '#default_value' => $config->get('path'),
      '#description' => $this
        ->t('The path for the search app (Default: "/search-app").'),
    ];

    $form['setup']['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search results page title'),
      '#default_value' => $config->get('page_title'),
      '#description' => $this
        ->t('The title that will live in the header tag of the search results page (leave empty to hide completely).'),
    ];

    $form['setup']['search_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#description' => $this->t('Defines <a href="/admin/config/search/search-api">which search_api index and server</a> the search app should use as a datasource.'),
      '#options' => $index_options,
      '#default_value' => $config->get('index.id'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'getSiteName'],
        'event' => 'change',
        'wrapper' => 'site-name-property',
      ],
    ];

    $form['setup']['disable_query_proxy'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Do not use the proxy for the search query') . '</b>',
      '#default_value' => $config->get('proxy.isDisabled'),
      '#description' => $this
        ->t('Check this box to configure the search app to query the Solr server directly, instead of using the Drupal route defined by this module as a proxy to the Solr backend of the chosen Search API index.  When checked, it is highly recommended that you also procure and configure read-only basic auth credentials for the search app.<br /><br />Note: Acquia Search customers must leave this box unchecked.'),
      '#attributes' => [
        'data-direct-query-enabler' => TRUE,
      ],
    ];

    $form['setup']['search_index_basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Index Basic Authentication'),
      '#description' => $this->t('If your Solr server is protected by basic HTTP authentication (highly recommended), enter the login data here. This will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.'),
      '#states' => [
        'visible' => [
          ':input[data-direct-query-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['setup']['search_index_basic_auth']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('index.username'),
    ];

    $form['setup']['search_index_basic_auth']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('index.password'),
    ];

    /**
     * Search results page options:
     *   - show empty search results (i.e. filterable listing page),
     *   - customize "no results" text
     *   - custom search prompt
     *     - renders in result area when show empty results no enabled and no query value
     *   - max number of search results per page
     *   - max number of "numbered" pagination buttons to show
     */

    $form['search_page_options'] = [
      '#type' => 'details',
      '#title' => 'Search Results Page > Options',
      '#open' => FALSE,
    ];

    $form['search_page_options']['show_empty_search_results'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Show results for empty search') . '</b>',
      '#default_value' => $config->get('content.show_empty_search_results'),
      '#description' => $this
        ->t(' When checked, this option allows users to see all results when no search term is entered. By default, empty searches are disabled and yield no results.'),
    ];

    $form['search_page_options']['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $config->get('content.no_results'),
      '#description' => $this
        ->t('This text is shown when a query returns no results. (Default: "Your search yielded no results.")'),
    ];

    $form['search_page_options']['search_prompt_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search prompt text'),
      '#default_value' => $config->get('content.search_prompt'),
      '#description' => $this
        ->t('This text is shown when no query term has been entered. (Default: "Please enter a search term.")'),
    ];

    $form['search_page_options']['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of search results per page'),
      '#default_value' => $config->get('results.rows'),
      '#description' => $this
        ->t('The max number of results to render per search results page. (Default: 20)'),
    ];

    $form['search_page_options']['page_buttons'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of pagination buttons'),
      '#default_value' => $config->get('pagination.buttons'),
      '#description' => $this
        ->t('The max number of numbered pagination buttons to show at a given time. (Default: 5)'),
    ];

    /**
     * Settings and values for search facets and filters:
     *   - set the site name facet to the current site name property
     */

    $form['search_form_values'] = [
      '#type' => 'details',
      '#title' => 'Search Results Page > Facets & Filters',
      '#open' => FALSE,
    ];

    /**
     * Set hidden form element value based on presence of field properties on
     *   the selected index.  This value will determine which inputs are
     *   visible for setting default facet/filter values and hiding in the UI.
     */

    $form['search_form_values']['site_name_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
      '#value' => $site_name_property_value,
      '#default_value' => $site_name_property_default_value,
    ];

    $form['search_form_values']['date_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['date-property'],
      ],
      '#value' => $config->get('index.has_federated_date_property') ? 'true' : '',
    ];

    $form['search_form_values']['type_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['type-property'],
      ],
      '#value' => $config->get('index.has_federated_type_property') ? 'true' : '',
    ];

    $form['search_form_values']['terms_property'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => ['terms-property'],
      ],
      '#value' => $config->get('index.has_federated_terms_property') ? 'true' : '',
    ];

    /**
     * Enable setting of default values for available facets / filter.
     * As of now, this includes Site Name only.
     */

    $form['search_form_values']['defaults'] = [
      '#type' => 'fieldset',
      '#title' => 'Set facet / filter default values'
    ];

    $form['search_form_values']['defaults']['set_search_site'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set the "Site name" facet to this site'),
      '#default_value' => $config->get('facet.site_name.set_default'),
      '#description' => $this
        ->t('When checked, only search results from this site will be shown, by default, until this site\'s checkbox is unchecked in the search app\'s "Site name" facet.'),
      '#states' => [
        'visible' => [
          ':input[name="site_name_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

    /**
     * Enable hiding available facets / filters.
     * These form elements will only be visible if their corresopnding
     *   property exists on the index.
     */
    $form['search_form_values']['hidden'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hide facets / filters from sidebar'),
      '#description' => $this->t('The checked facets / filters will be hidden from the search app.'),
    ];

    $form['search_form_values']['hidden']['hide_site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Site name facet'),
      '#default_value' => $config->get('facet.site_name.is_hidden'),
      '#description' => $this
        ->t('When checked, the ability to which sites should be included in the results will be hidden.'),
      '#states' => [
        'visible' => [
          ':input[name="site_name_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

    $form['search_form_values']['hidden']['hide_type'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Type facet'),
      '#default_value' => $config->get('facet.federated_type.is_hidden'),
      '#description' => $this
        ->t('When checked, the ability to select those types (i.e. bundles) which should have results returned will be hidden.'),
      '#states' => [
        'visible' => [
          ':input[name="type_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

    $form['search_form_values']['hidden']['hide_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Date filter'),
      '#default_value' => $config->get('filter.federated_date.is_hidden'),
      '#description' => $this
        ->t('When checked, the ability to filter results by date will be hidden.'),
      '#states' => [
        'visible' => [
          ':input[name="date_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

    $form['search_form_values']['hidden']['hide_terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Terms facet'),
      '#default_value' => $config->get('facet.federated_terms.is_hidden'),
      '#description' => $this
        ->t('When checked, the ability to select those terms which should have results returned will be hidden.'),
      '#states' => [
        'visible' => [
          ':input[name="terms_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

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
      '#title' => $this->t('Search Results Page > Search Form > Autocomplete'),
      '#description' => $this->t('These options apply to the autocomplete functionality on the search for which appears above the search results on the search results page.  Configure your placed Federated Search Page Form block to add autocomplete to that form.'),
      '#open' => $config->get('autocomplete.isEnabled'),
    ];

    $form['autocomplete']['autocomplete_is_enabled'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Enable autocomplete for the search results page search form') . '</b>',
      '#default_value' => $config->get('autocomplete.isEnabled'),
      '#description' => $this
        ->t('Check this box to enable autocomplete on the search results page search form and to expose more configuration options below.'),
      '#attributes' => [
        'data-autocomplete-enabler' => TRUE,
      ],
    ];

    $form['autocomplete']['autocomplete_is_append_wildcard'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Append a wildcard \'*\' to support partial text search') . '</b>',
      '#default_value' => $config->get('autocomplete.appendWildcard'),
      '#description' => $this
        ->t('Check this box to append a wildcard * to the end of the autocomplete query term (i.e. "car" becomes "car*").  This option is only recommended if your solr config does not add a field(s) with <a href="https://lucene.apache.org/solr/guide/6_6/tokenizers.html" target="_blank">NGram Tokenizers</a> to your index or if your <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> is not configured to search those fields.'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_disable_query_proxy'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Do not use the proxy for the search app autocomplete query') . '</b>',
      '#default_value' => $config->get('autocomplete.proxy.isDisabled'),
      '#description' => $this
        ->t('Check this box to configure the search app to query the Solr server directly for autocomplete, instead of using the Drupal route defined by this module as a proxy to the Solr backend of the Search API index chosen above in Search Results Page > Set Up.  When checked, it is highly recommended that you also procure and configure read-only basic auth credentials and use them here.<br /><br />Note: Acquia Search customers must leave this box unchecked.'),
      '#attributes' => [
        'data-autocomplete-direct-query-enabler' => TRUE,
      ],
    ];

    $form['autocomplete']['direct'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autocomplete Direct Query Settings'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-direct-query-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['autocomplete_direct_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Solr Endpoint URL'),
      '#default_value' => $config->get('autocomplete.direct.url'),
      '#maxlength' => 2048,
      '#size' => 50,
      '#description' => $this
        ->t('The URL where requests for autocomplete queries should be made. (Default: the url of the  <code>select</code> <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> on the server of the selected Search API index.)<ul><li>Supports an absolute url pattern to any other Request Handler for an index on your solr server</li><li>The value of the main search field will be appended to the url as the main query param (i.e. <code>?q=[value of the search field, wildcard appended if enabled]</code>)</li><li>Any facet/filter default values set for the search app will automatically be appended (i.e. <code>&sm_site_name=[value of the site name for the index]</code>)</li><li>The format param <code>&wt=json</code> will automatically be appended</li><li>Include any other necessary url params corresponding to <a href="https://lucene.apache.org/solr/guide/6_6/common-query-parameters.html" target="_blank">query parameters</a>.</li></ul>'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-direct-query-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search App Autocomplete Endpoint Basic Authentication'),
      '#description' => $this->t('If your Solr server is protected by basic HTTP authentication (highly recommended), enter the login data here. This will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-direct-query-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['autocomplete_use_search_app_creds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use credentials provided for Search Index Basic Authentication in Search Results Page > Set Up above'),
      '#default_value' => $config->get('autocomplete.use_search_app_creds'),
      '#attributes' => [
        'data-autocomplete-use-search-app-creds' => TRUE,
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['autocomplete_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('autocomplete.username'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-use-search-app-creds]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['direct']['basic_auth']['autocomplete_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('autocomplete.password'),
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
      '#default_value' => $config->get('autocomplete.suggestionRows'),
      '#description' => $this
        ->t('The max number of results to render in the autocomplete results dropdown. (Default: 5)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_num_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters after which autocomplete query should execute'),
      '#default_value' => $config->get('autocomplete.numChars'),
      '#description' => $this
        ->t('Autocomplete query will be executed <em>after</em> a user types this many characters in the search query field. (Default: 2)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $autocomplete_mode = $config->get('autocomplete.mode');
    $title_text_config_key = 'autocomplete.' . $autocomplete_mode . '.titleText';
    $hide_directions_text_config_key = 'autocomplete.' . $autocomplete_mode . '.hideDirectionsText';

    $form['autocomplete']['autocomplete_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocomplete mode'),
      '#description' => $this->t('Type of results the autocomplete response returns: search results (default) or search terms.'),
      '#options' => [
        'result' => $this
          ->t('Search results (i.e. search as you type functionality)'),
        'Search terms (coming soon)' => [],
      ],
      '#default_value' => $autocomplete_mode || 'result',
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_title_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Results title text'),
      '#size' => 50,
      '#default_value' => $autocomplete_mode ? $config->get($title_text_config_key) : '',
      '#description' => $this
        ->t('The title text is shown above the results in the autocomplete drop down.  (Default: "What are you interested in?" for Search Results mode and "What would you like to search for?" for Search Term mode.)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['autocomplete']['autocomplete_mode_hide_directions'] = [
      '#type' => 'checkbox',
      '#title' => '<b>' . $this->t('Hide keyboard directions') . '</b>',
      '#default_value' => $autocomplete_mode ? $config->get($hide_directions_text_config_key) : 0,
      '#description' => $this
        ->t('Check this box to make hide the autocomplete keyboard usage directions in the results dropdown. For sites that want to maximize their accessibility UX for sighted keyboard users, we recommend leaving this unchecked. (Default: directions are visible)'),
      '#states' => [
        'visible' => [
          ':input[data-autocomplete-enabler]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['#cache'] = ['max-age' => 0];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the search app configuration.
    $config = $this->configFactory->getEditable('search_api_federated_solr.search_app.settings');

    // Set the search app path.
    $path = $form_state->getValue('path');
    $current_path = $config->get('path');
    $rebuild_routes = FALSE;
    if ($path && $path !== $current_path) {
      $config->set('path', $path);
      $rebuild_routes = TRUE;
    }

    // Set the search results page title.
    $page_title = $form_state->getValue('page_title');
    $config->set('page_title', $page_title);

    // Set the search app config setting for the default search site flag.
    $set_search_site = $form_state->getValue('set_search_site');
    $config->set('facet.site_name.set_default', $set_search_site);

    // Set the search app config settings for hidden filter/facets.
    $hide_search_site = $form_state->getValue('hide_site_name');
    $config->set('facet.site_name.is_hidden', $hide_search_site);

    $hide_type = $form_state->getValue('hide_type');
    $config->set('facet.federated_type.is_hidden', $hide_type);

    $hide_terms = $form_state->getValue('hide_terms');
    $config->set('facet.federated_terms.is_hidden', $hide_terms);

    $hide_date = $form_state->getValue('hide_date');
    $config->set('filter.federated_date.is_hidden', $hide_date);

    // Set the search app configuration setting for the default search site flag.
    $show_empty_search_results = $form_state->getValue('show_empty_search_results');
    $config->set('content.show_empty_search_results', $show_empty_search_results);

    // Set the proxy url from the proxy route
    $proxy_url_options = [
      'absolute' => TRUE,
    ];
    $proxy_url_object = Url::fromRoute('search_api_federated_solr.solr_proxy', [], $proxy_url_options);
    $proxy_url = $proxy_url_object->toString();
    $config->set('proxy.url', $proxy_url);

    // Determine whether or not we should be using the proxy.
    $proxy_is_disabled = $form_state->getValue('disable_query_proxy');
    $config->set('proxy.isDisabled', $proxy_is_disabled);

    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Save the selected index option in search app config (for form state).
    $config->set('index.id', $search_index);

    // Get the id of the chosen index's server.
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
    // Append the request handler.
    $server_url .= '/select';

    // Set the search app configuration setting for the solr backend url.
    $config->set('index.server_url', $server_url);

    // Set the Basic Auth username and password.
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');
    $config->set('index.username', $username);
    $config->set('index.password', $password);

    // Set the no results text.
    $config->set('content.no_results', $form_state->getValue('no_results_text'));

    // Set the search prompt text.
    $config->set('content.search_prompt', $form_state->getValue('search_prompt_text'));

    // Set the number of rows.
    $config->set('results.rows', $form_state->getValue('rows'));

    // Set the number of pagination buttons.
    $config->set('pagination.buttons', $form_state->getValue('page_buttons'));

    // Set autocomplete options.
    $autocomplete_is_enabled = $form_state->getValue('autocomplete_is_enabled');
    $config->set('autocomplete.isEnabled', $form_state->getValue('autocomplete_is_enabled'));

    // If enabled, set the autocomplete options.
    if ($autocomplete_is_enabled) {
      // Cache form values that we'll use more than once.
      $autocomplete_direct_url_value = $form_state->getValue('autocomplete_direct_url');
      $autocomplete_mode = $form_state->getValue('autocomplete_mode');

      // Set the default autocomplete direct endpoint url to the default search url if none was passed in.
      $autocomplete_direct_url = $autocomplete_direct_url_value ? $autocomplete_direct_url_value : $server_url;

      // Determine the url to be used for autocomplete queries based on proxy flag.
      $proxy_is_disabled = $form_state->getValue('disable_query_proxy');
      $autocomplete_url = $proxy_is_disabled ? $autocomplete_direct_url : $proxy_url;

      // Default to the form values
      $autocomplete_username = $form_state->getValue('autocomplete_username');
      $autocomplete_password = $form_state->getValue('autocomplete_password');
      $use_search_app_creds = $form_state->getValue('autocomplete_use_search_app_creds');
      // Add basic auth credentials
      if ($use_search_app_creds) {
        $autocomplete_username = $username;
        $autocomplete_password = $password;
      }

      // Set the actual autocomplete config options.
      $config->set('autocomplete.proxy.isDisabled', $proxy_is_disabled);
      $config->set('autocomplete.proxy.url', $proxy_url);
      $config->set('autocomplete.direct.url', $autocomplete_direct_url);
      $config->set('autocomplete.url', $autocomplete_url);
      $config->set('autocomplete.appendWildcard', $form_state->getValue('autocomplete_is_append_wildcard'));
      $config->set('autocomplete.use_search_app_creds', $use_search_app_creds);
      $config->set('autocomplete.username', $autocomplete_username);
      $config->set('autocomplete.password', $autocomplete_password);
      $config->set('autocomplete.suggestionRows', $form_state->getValue('autocomplete_suggestion_rows'));
      $config->set('autocomplete.numChars', $form_state->getValue('autocomplete_num_chars'));
      if ($autocomplete_mode) {
        $config->set('autocomplete.mode', $autocomplete_mode);
        $title_text_config_key = 'autocomplete.' . $autocomplete_mode . '.titleText';
        $config->set($title_text_config_key, $form_state->getvalue('autocomplete_mode_title_text'));
        $hide_directions_config_key = 'autocomplete.' . $autocomplete_mode . '.hideDirectionsText';
        $config->set($hide_directions_config_key, $form_state->getValue('autocomplete_mode_hide_directions'));
      }
    }
    $config->save();

    if ($rebuild_routes) {
      // Rebuild the routing information without clearing all the caches.
      \Drupal::service('router.builder')->rebuild();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Get the name of the site.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $elem
   *   Hidden form element used to flag the state of the site name in the index.
   */
  public function getSiteName(array &$form, FormStateInterface $form_state) {
    // Get the id of the chosen index.
    $search_index = $form_state->getValue('search_index');
    // Get the index configuration object.
    $index_config = \Drupal::config('search_api.index.' . $search_index);
    $is_site_name_property = $index_config->get('field_settings.site_name.configuration.site_name') ? 'true' : '';

    $elem = [
      '#type' => 'hidden',
      '#name' => 'site_name_property',
      '#value' => $is_site_name_property,
      '#attributes' => [
        'id' => ['site-name-property'],
      ],
    ];

    return $elem;
  }

  /**
   * Validates that the provided search path is not in use by an existing route.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function formValidationPathValidate(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('path');
    if ($path) {
      // Check if a route with the config path value already exists.
      $router = \Drupal::service('router.no_access_checks');
      $result = FALSE;
      try {
        $result = $router->match($path);
      }
      catch (\Exception $e) {
        // This is what we want, indicates the route path doesn't exist.
      }
      // If the route path exists for something other than the search route,
      // set an error on the form.
      if ($result && $result['_route'] !== "search_api_federated_solr.search") {
        $form_state->setErrorByName('path', t('The path you have entered already exists'));
      }
    }
  }

}
