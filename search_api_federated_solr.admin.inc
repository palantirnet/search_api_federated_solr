<?php

/**
 * @file search_api_federated_solr.admin.inc
 * Contains form implementations for the Federated Solr Search API Module.
 *
 * @copyright Copyright (c) 2018-19 Palantir.net
 */

/**
 * Create search_api_federated_solr config form.
 */
function search_api_federated_solr_admin($form, &$form_state) {

  // Get search indexes.
  $indexes = [];
  foreach (search_api_index_load_multiple(FALSE) as $index) {
    $indexes[$index->machine_name] = $index->name;
    $settings[$index->machine_name] = $index->options;
  }

  $form['#prefix'] = '<div id="search-api-federated-solr-config-form">';
  $form['#suffix'] = '</div>';

  $form['setup'] = [
    '#type' => 'fieldset',
    '#title' => 'Search Results Page > Set Up',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  ];

  $form['setup']['search_api_federated_solr_path'] = [
    '#type' => 'textfield',
    '#title' => t('Search app path'),
    '#default_value' => variable_get('search_api_federated_solr_path', 'search-app'),
    '#description' => t('The path for the search app (Default: "search-app").'),
  ];

  $form['setup']['search_api_federated_solr_search_index'] = [
    '#type' => 'select',
    '#title' => t('Search API index'),
    '#description' => t('Defines <a href="/admin/config/search/search-api">which search_api index and server</a> the search app should use.'),
    '#options' => $indexes,
    '#default_value' => variable_get('search_api_federated_solr_search_index'),
    '#required' => TRUE,
    '#ajax' => [
      'callback' => 'get_site_name',
      'wrapper' => 'search-api-federated-solr-config-form',
    ],
  ];

  $form['setup']['search_api_federated_solr_disable_query_proxy'] = [
    '#type' => 'checkbox',
    '#title' => '<strong>' . t('Do not use the proxy for the search query') . '</strong>',
    '#default_value' => variable_get('search_api_federated_solr_disable_query_proxy'),
    '#description' => t('Check this box to configure the search app to query the Solr server directly. When checked, it is highly recommended that you also procure and configure read-only basic auth credentials for the search app. When unchecked, this site will act as a proxy for requests to the Solr server of the chosen Search API index using the Drupal route defined by this module.<br/><br/>Note: Acquia Search customers must leave this box unchecked.'),
    '#attributes' => [
      'data-direct-query-enabler' => TRUE,
    ],
  ];

  $form['setup']['search_api_federated_solr_search_index_basic_auth'] = [
    '#type' => 'fieldset',
    '#title' => t('Search Index Basic Authentication'),
    '#description' => t('If your Solr server is protected by basic HTTP authentication, enter the login data here. These credentials will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.'),
    '#states' => [
      'visible' => [
        ':input[data-direct-query-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['setup']['search_api_federated_solr_search_index_basic_auth']['search_api_federated_solr_search_index_basic_auth_username'] = [
    '#type' => 'textfield',
    '#title' => t('Username'),
    '#default_value' => variable_get('search_api_federated_solr_search_index_basic_auth_username'),
    '#states' => [
      'visible' => [
        ':input[data-direct-query-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['setup']['search_api_federated_solr_search_index_basic_auth']['search_api_federated_solr_search_index_basic_auth_password'] = [
    '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => variable_get('search_api_federated_solr_search_index_basic_auth_password'),
    '#states' => [
      'visible' => [
        ':input[data-direct-query-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
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
    '#type' => 'fieldset',
    '#title' => 'Search Results Page > Options',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  $form['search_page_options']['search_api_federated_solr_show_empty_search_results'] = [
    '#type' => 'checkbox',
    '#title' => t('Show results for empty search'),
    '#default_value' => variable_get('search_api_federated_solr_show_empty_search_results'),
    '#description' => t('When checked, this option allows users to see all results when no search term is entered. By default, empty searches are disabled and yield no results.'),
  ];

  $form['search_page_options']['search_api_federated_solr_no_results_text'] = [
    '#type' => 'textfield',
    '#title' => t('No results text'),
    '#default_value' => variable_get('search_api_federated_solr_no_results_text'),
    '#description' => t('This text is shown when a query returns no results. (Default: "Your search yielded no results.")'),
  ];

  $form['search_page_options']['search_api_federated_solr_search_prompt_text'] = [
    '#type' => 'textfield',
    '#title' => t('Search prompt text'),
    '#default_value' => variable_get('search_api_federated_solr_search_prompt_text'),
    '#description' => t('This text is shown when no query term has been entered. (Default: "Please enter a search term.")'),
  ];

  $form['search_page_options']['search_api_federated_solr_rows'] = [
    '#type' => 'textfield',
    '#attributes' => array(
      ' type' => 'number',
    ),
    '#title' => t('Number of search results per page'),
    '#default_value' => variable_get('search_api_federated_solr_rows'),
    '#description' => t('The max number of results to render per search results page. (Default: 20)'),
  ];

  $form['search_page_options']['search_api_federated_solr_page_buttons'] = [
    '#type' => 'textfield',
    '#attributes' => array(
      ' type' => 'number',
    ),
    '#title' => t('Number of pagination buttons'),
    '#default_value' => variable_get('search_api_federated_solr_page_buttons'),
    '#description' => t('The max number of numbered pagination buttons to show at a given time. (Default: 5)'),
  ];

  /**
   * Settings and values for search facets and filters:
   *   - set the site name facet to the current site name property
   */

  $form['search_form_values'] = [
    '#type' => 'fieldset',
    '#title' => 'Search Results Page > Facets & Filters',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];

  /**
   * Set hidden form element value based on presence of field properties on
   *   the selected index.  This value will determine which inputs are
   *   visible for setting default facet/filter values and hiding in the UI.
   */
  $form['search_form_values']['defaults'] = [
    '#type' => 'fieldset',
    '#title' => 'Set facet / filter default values'
  ];

  $form['search_form_values']['defaults']['search_api_federated_solr_set_search_site'] = [
    '#type' => 'checkbox',
    '#title' => t('Set the "Site name" facet to this site'),
    '#default_value' => variable_get('search_api_federated_solr_set_search_site'),
    '#description' => t('When checked, only search results from this site will be shown, by default, until this site\'s checkbox is unchecked in the search app\'s "Site name" facet.<br />This feature works best when the <strong>proxy server</strong> is enabled.'),
    '#states' => [
      'visible' => [
        ':input[name="search_api_federated_solr_has_site_name_property"]' => [
          'value' => "true"
        ],
      ],
    ],
  ];

    $site_list = variable_get('search_api_federated_solr_site_list');
    $sites = [];
    foreach ($site_list as $site) {
      $sites[$site] = $site;
    }
    $form['search_form_values']['defaults']['search_api_federated_solr_allowed_sites'] = [
      '#type' => 'checkboxes',
      '#options' => $sites,
      '#title' => t('Sites that may be searched from this instance'),
      '#default_value' => variable_get('search_api_federated_solr_allowed_sites', []),
      '#description' => t('When at least one option is checked, only search results from these sites will be shown as options in the search app\'s "Site name" facet. Default searches will only query the selected sites. If no options are checked, all sites in the network will be available. If no options are visible, you will need to configure your site list in settings.php. See <a href="!url">the help page for information</a>',
        ['!url' => '/admin/help/search_api_federated_solr']),
      '#states' => [
        'visible' => [
          ':input[name="site_name_property"]' => [
            'value' => "true",
          ],
        ],
      ],
    ];

  if (!empty(variable_get('search_api_federated_solr_search_index'))) {
    $index = variable_get('search_api_federated_solr_search_index');
    if (isset($settings[$index])) {
      $fields = $settings[$index]['fields'];
    }
  }

  $form['search_form_values']['search_api_federated_solr_has_site_name_property'] = [
    '#type' => 'hidden',
    '#attributes' => [
      'id' => ['site-name-property'],
    ],
    '#default_value' => isset($fields['site_name']) ? 'true' : variable_get('search_api_federated_solr_has_site_name_property'),
  ];

  $form['search_form_values']['search_api_federated_solr_has_federated_date_property'] = [
    '#type' => 'hidden',
    '#attributes' => [
      'id' => ['date-property'],
    ],
    '#value' => isset($fields['federated_date']) ? 'true' : variable_get('search_api_federated_solr_has_federated_date_property', 'true'),
  ];

  $form['search_form_values']['search_api_federated_solr_has_federated_type_property'] = [
    '#type' => 'hidden',
    '#attributes' => [
      'id' => ['type-property'],
    ],
    '#value' => isset($fields['federated_type']) ? 'true' : variable_get('search_api_federated_solr_has_federated_type_property', 'true'),
  ];

  $form['search_form_values']['search_api_federated_solr_has_federated_terms_property'] = [
    '#type' => 'hidden',
    '#attributes' => [
      'id' => ['terms-property'],
    ],
    '#value' => isset($fields['federated_terms']) ? 'true' : variable_get('search_api_federated_solr_has_federated_terms_property', 'true'),
  ];

  /**
   * Enable hiding available facets / filters.
   * These form elements will only be visible if their corresopnding
   *   property exists on the index.
   */
  $form['search_form_values']['hidden'] = [
    '#type' => 'fieldset',
    '#title' => t('Hide facets / filters from sidebar'),
    '#description' => t('The checked facets / filters will be hidden from the search app.'),
  ];

  $form['search_form_values']['hidden']['search_api_federated_solr_hide_site_name'] = [
    '#type' => 'checkbox',
    '#title' => t('Site name facet'),
    '#default_value' => variable_get('search_api_federated_solr_hide_site_name'),
    '#description' => t('When checked, the ability to select which sites should be included in the results will be hidden.'),
    '#states' => [
      'visible' => [
        ':input[name="site_name_property"]' => [
          'value' => "true",
        ],
      ],
    ],
  ];

  $form['search_form_values']['hidden']['search_api_federated_solr_hide_type'] = [
    '#type' => 'checkbox',
    '#title' => t('Type facet'),
    '#default_value' => variable_get('search_api_federated_solr_hide_type'),
    '#description' => t('When checked, the ability to select those types (i.e. bundles) which should have results returned will be hidden.'),
    '#states' => [
      'visible' => [
        ':input[name="type_property"]' => [
          'value' => "true",
        ],
      ],
    ],
  ];

  $form['search_form_values']['hidden']['search_api_federated_solr_hide_date'] = [
    '#type' => 'checkbox',
    '#title' => t('Date filter'),
    '#default_value' => variable_get('search_api_federated_solr_hide_date'),
    '#description' => t('When checked, the ability to filter results by date will be hidden.'),
    '#states' => [
      'visible' => [
        ':input[name="date_property"]' => [
          'value' => "true",
        ],
      ],
    ],
  ];

  $form['search_form_values']['hidden']['search_api_federated_solr_hide_terms'] = [
    '#type' => 'checkbox',
    '#title' => t('Terms facet'),
    '#default_value' => variable_get('search_api_federated_solr_hide_terms'),
    '#description' => t('When checked, the ability to select those terms which should have results returned will be hidden.'),
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
    '#type' => 'fieldset',
    '#title' => t('Search Results Page > Search Form > Autocomplete'),
    '#description' => '<p>' . t('These options apply to the autocomplete functionality on the search for which appears above the search results on the search results page.  Configure your placed Federated Search Page Form block to add autocomplete to that form.') . '</p>',
    '#collapsible' => TRUE,
    '#collapsed' => !variable_get('search_api_federated_solr_autocomplete_is_enabled'),
  ];

  $form['autocomplete']['search_api_federated_solr_autocomplete_is_enabled'] = [
    '#type' => 'checkbox',
    '#title' => '<b>' . t('Enable autocomplete for the search results page search form') . '</b>',
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_is_enabled'),
    '#description' => t('Checking this will expose more configuration options for autocomplete behavior for the search form on the Search Results page at the end of this form.'),
    '#attributes' => [
      'id' => ['autocomplete-is-enabled'],
    ],
  ];

  $form['autocomplete']['search_api_federated_solr_autocomplete_is_append_wildcard'] = [
    '#type' => 'checkbox',
    '#title' => '<b>' . t('Append a wildcard \'*\' to support partial text search') . '</b>',
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_is_append_wildcard'),
    '#description' => t('Check this box to append a wildcard * to the end of the autocomplete query term (i.e. "car" becomes "car+car*").  This option is recommended if your solr config does not add a field(s) with <a href="https://lucene.apache.org/solr/guide/6_6/tokenizers.html" target="_blank">NGram Tokenizers</a> to your index or if your autocomplete <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> is not configured to search those fields.'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['search_api_federated_solr_autocomplete_disable_query_proxy'] = [
    '#type' => 'checkbox',
    '#title' => '<strong>' . t('Do not use the proxy for the search app autocomplete query') . '</strong>',
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_disable_query_proxy', 0),
    '#description' => t('Check this box to configure the search app to query the Solr server directly. When checked, it is highly recommended that you also procure and configure read-only basic auth credentials for the search app. When unchecked, this site will act as a proxy for requests to the Solr server of the Search API index chosen above in Search Results Page > Set Up using the Drupal route defined by this module.<br/><br/>Note: Acquia Search customers must leave this box unchecked.'),
    '#attributes' => [
      'data-autocomplete-direct-query-enabler' => TRUE,
    ],
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['direct'] = [
    '#type' => 'fieldset',
    '#title' => t('Autocomplete Direct Query Settings'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-direct-query-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['direct']['search_api_federated_solr_autocomplete_url'] = [
    '#type' => 'textfield',
    '#title' => t('Solr Endpoint URL'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_url'),
    '#maxlength' => 2048,
    '#size' => 50,
    '#description' => t('The URL where requests for autocomplete queries should be made. (Default: the url of the  <code>select</code> <a href="https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-RequestHandlers" target="_blank">Request Handler</a> on the server of the selected Search API index.)<ul><li>Supports an absolute url pattern to any other Request Handler for an index on your solr server</li><li>The value of the main search field will be appended to the url as the main query param (i.e. <code>?q=[value of the search field, wildcard appended if enabled]</code>)</li><li>Any facet/filter default values set for the search app will automatically be appended (i.e. <code>&sm_site_name=[value of the site name for the index]</code>)</li><li>The format param <code>&wt=json</code> will automatically be appended</li><li>Include any other necessary url params corresponding to <a href="https://lucene.apache.org/solr/guide/6_6/common-query-parameters.html" target="_blank">query parameters</a>.</li>'),
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
    '#title' => t('Search App Autocomplete Endpoint Basic Authentication'),
    '#description' => '<p>' . t('If your Solr server is protected by basic HTTP authentication (highly recommended), enter the login data here. These credentials will be accessible to the client in an obscured, but non-secure method. It should, therefore, only provide read access to the index AND be different from that provided when configuring the server in Search API. The Password field is intentionally not obscured to emphasize this distinction.') . '</p>',
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-direct-query-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['direct']['basic_auth']['search_api_federated_solr_autocomplete_use_search_app_creds'] = [
    '#type' => 'checkbox',
    '#title' => t('Use credentials provided for Search Index Basic Authentication in Search Results Page > Set Up above'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_use_search_app_creds'),
    '#attributes' => [
      'data-autocomplete-use-search-app-creds' => TRUE,
    ],
  ];

  $form['autocomplete']['direct']['basic_auth']['search_api_federated_solr_autocomplete_username'] = [
    '#type' => 'textfield',
    '#title' => t('Username'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_username'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-use-search-app-creds]' => [
          'checked' => FALSE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['direct']['basic_auth']['search_api_federated_solr_autocomplete_password'] = [
    '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_password'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-use-search-app-creds]' => [
          'checked' => FALSE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['search_api_federated_solr_autocomplete_suggestion_rows'] = [
    '#type' => 'textfield',
    '#title' => t('Number of results'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_suggestion_rows'),
    '#description' => t('The max number of results to render in the autocomplete results dropdown. (Default: 5)'),
    '#size' => 5,
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete']['search_api_federated_solr_autocomplete_num_chars'] = [
    '#type' => 'textfield',
    '#title' => t('Number of characters after which autocomplete query should execute'),
    '#default_value' => variable_get('search_api_federated_solr_autocomplete_num_chars'),
    '#description' => t('Autocomplete query will be executed <em>after</em> a user types this many characters in the search query field. (Default: 2)'),
    '#size' => 5,
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $autocomplete_mode = variable_get('search_api_federated_solr_autocomplete_mode') ?: 'result' ;
  $title_text_config_key = 'search_api_federated_solr_autocomplete_' . $autocomplete_mode . '_title_text';
  $hide_directions_text_config_key = 'search_api_federated_solr_autocomplete_' . $autocomplete_mode . '_hide_directions_text';

  $form['autocomplete']['search_api_federated_solr_autocomplete_mode'] = [
    '#type' => 'select',
    '#title' => t('Autocomplete mode'),
    '#description' => t('Type of results the autocomplete response returns: search results (default) or search terms.'),
    '#options' => [
      'result' => t('Search results (i.e. search as you type functionality)'),
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

  $form['autocomplete'][$title_text_config_key] = [
    '#type' => 'textfield',
    '#title' => t('Results title text'),
    '#size' => 50,
    '#default_value' => $autocomplete_mode ? variable_get($title_text_config_key) : '',
    '#description' => t('The title text is shown above the results in the autocomplete drop down.  (Default: "What are you interested in?")'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['autocomplete'][$hide_directions_text_config_key] = [
    '#type' => 'checkbox',
    '#title' => '<b>' . t('Hide keyboard directions') . '</b>',
    '#default_value' => $autocomplete_mode ? variable_get($hide_directions_text_config_key) : 0,
    '#description' => t('Check this box to make hide the autocomplete keyboard usage directions in the results dropdown. For sites that want to maximize their accessibility UX for sighted keyboard users, we recommend leaving this unchecked. (Default: directions are visible)'),
    '#states' => [
      'visible' => [
        ':input[data-autocomplete-enabler]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ];

  $form['#cache'] = ['max-age' => 0];

  return system_settings_form($form);
}
