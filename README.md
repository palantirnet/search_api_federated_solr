INTRODUCTION
------------

Search API Federated Solr is an open source alternative to the Google Search Appliance and other technologies that index and search across multiple web sites.

The module facilitates indexing data from multiple Drupal 7 and 8 sites into a single Solr search index.  The module provides a ReactJS front end that presents uniform search results across the different sites. As an advanced case, the system can also be used to index non-Drupal content and display it as well. The only requirement is following the required scheme for content indexing, described below.

The primary features of the module include:

 * Indexing of multiple, independent Drupal sites into a single index
 * Optional filtering of search results by site
 * Standard presentation of search results on all sites
 * A standard search block for use on all sites with optional configurable type-ahead search results
 * Customizable presentation using a single CSS file

The user interface and default filter and autocomplete features also make the module appropriate for running even on a single site.

### How does it work?

Searching across multiple sites requires a common set of data. By design, Search API Federated Solr indexes the following information for each item in the search index:

 * Title
 * Date (content creation or any other date field on the content)
 * Content Type
 * Content Tags (all taxonomy terms)
 * Preview Image, if available
 * Full Text
 * URI
 * Site Name
 * Site Base URI

These nine data points represent the most common requirements in search results. They allow for uniform searching and presentation and for the faceting of results.

Because our data is served by an external application, all sites will need to conform to a set field schema when sending data to the index. Extra fields may be sent for use by individual sites, but they will not be read by the React application.

See the *Technical Notes* section at the end of the document for more information about how these fields are configured.

REQUIREMENTS
------------

Search API Federated Solr 8.x-3.x requires the following modules:

 * Search API (https://www.drupal.org/project/search_api)
 * Search API Field Map (https://github.com/palantirnet/search_api_field_map) version 8.x-3.x
 * SeachAPI Solr (https://www.drupal.org/project/search_api_solr) version 8.x-3.x
 * Token (https://www.drupal.org/project/token)

The Search API Field Map module is used to provide common indexing across each site. See that module’s documentation for additional information.

The module also relies on the [Federated Search React](https://github.com/palantirnet/federated-search-react) application, which is referenced as an external Drupal library.

Apache Solr versions `7.7.2` have been used with this module and it is likely that newer versions will also work.

### Older Versions

The 8.x-2.x version of this module supports SearchAPI Solr 8.x-1.x and Solr 4.5 - 6.x.

INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

### Solr Schemas

This version of the module has been tested with the Schema files that ship with the 7.x-1.15 and 8.x-3.8 versions of the Search API Solr module. We recommend using the `7.x` schema files that ship with the 7.x-1.15 version.

A few things to note when using these schema files:

#### 7.x-1.15

The `7.x` schema files work by default when using this module. Note that on Drupal 8, you may see a warning: `There are some language-specific field types missing in schema of Solr server Drupal 7 schema - default: en.`

This warning is due to the fact that the 8.x-3.x version of SearchAPI Solr requires the language module to be installed and assumes that search is language-sensitive. Federated Search only indexes content in a site's primary language. This warning can safely be ignored.

#### 8.x-3.8

The 8.x-3.8 version of SearchAPI Solr does not ship with default configuration files. Instead, it dynamically generates the files based on your configuration. If you use the exported configuration files, you may need to make the following edits.

1. Add the following definition to `schema.xml`:

`  <field name="content" type="text_ws" indexed="true" stored="true" termVectors="true"/>`

This addition brings the schema into parity with how the Drupal 7 version of the module behaves.

2. Update the query handler to use `tm_rendered_item` in `solrconfig_extra.xml`:

```
<requestHandler name="/select" class="solr.SearchHandler">
  <lst name="defaults">
    <str name="defType">lucene</str>
    <str name="df">tm_rendered_item</str>
    <str name="echoParams">explicit</str>
    <str name="omitHeader">true</str>
    <str name="timeAllowed">${solr.selectSearchHandler.timeAllowed:-1}</str>
    <str name="spellcheck">false</str>
  </lst>
  <arr name="last-components">
    <str>spellcheck</str>
    <str>elevator</str>
  </arr>
</requestHandler>
```

This change (setting the `df` or `default fields` value) will force the search index to search for our federated content. If you only search from a Drupal instance, you may also set this value in `settings.php` if you prefer, as specified in the Configuration documentation below.

CONFIGURATION
-------------

On each site included in the federated search, you will need to:

1. Install this module and its dependencies.
  * Be aware that the 8.x-3.x version of Search API Solr requires a change to core files in order to run. See that module's documentation for more information.
1. Configure a Search API server to connect to the shared Solr index.
1. Configure a Search API index according to the [required schema documentation](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
    * Optional: To help facilitate autocomplete term partial queries, consider adding a Fulltext [Edge Ngram](https://lucene.apache.org/solr/guide/6_6/tokenizers.html) version of your title field to the index (See [example](https://github.com/palantirnet/federated-search-demo/blob/master/config/sites/d8/search_api.index.federated_search_index.yml#L86) in the Federated Search Demo site Solr index config).  Also consider adding that field as a default query field for your Solr server's default Request Handler.
    * Optional: If your site uses a "search terms" or similar field to trigger a boost for items based on given search terms, consider adding a Fulltext [Edge Ngram](https://lucene.apache.org/solr/guide/6_6/tokenizers.html) version of that field to the index.  Also consider adding that field as a default query field for your Solr server's default Request Handler.
1. Optional: Configure default fields for queries.  The default query field for search queries made through the proxy provided by this module is the `tm_rendered_item` field.  To set a different value for the default query fields there are two options:
    1. Set `$config['search_api_federated_solr.search_app.settings']['index']['query_fields']` to an array of _Fulltext_ field machine names (i.e. `['rendered_item', 'full_text_title']`) from your search index in `settings.php`.
        - This method will not work if you disable the proxy that this module provides for querying your Solr backend in the search app or block autocomplete settings
        - By default, the proxy will validate the field names to ensure that they are full text and that they exist on the index for this site.  Then it will translate the index field name into its Solr field name counterpart.  If you need to disable this validation + transformation (for example to search fields on a D7 site index whose machine names are different than the D8 site counterpart), set `$config['search_api_federated_solr.search_app.settings']['index']['validate_query_fields']` to `FALSE`.  Then you must supply the _solr field names_.  To determine what these field names are on your D8 site, use the drush command `drush sapifs-f`, which will output a table with index field names and their Solr field name counterparts.
    1. Configure that Search API server to set default query fields for your default [Request Handler](https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-SearchHandlers). (See [example](https://github.com/palantirnet/federated-search-demo/blob/solr-7/conf/solr/drupal8/custom/solr-conf/7.x/solrconfig_extra.xml) in the Federated Search Demo site Solr server config.)
1. Optional: Configure a list of sites that you wish to search from this instance. You can restrict the list of sites to search by adding configuration to your `settings.php` file.
    1. Set `$config['search_api_federated_solr.search_app.settings']['facet']['site_name']['site_list']` to an array of site name for your sites. This can normally be left blank if you wish to search all sites in your installed cluster. The array should normally include all sites in your cluster and be in the format:
       ```
       $config['search_api_federated_solr.search_app.settings']['facet']['site_name']['site_list'] = [
         'Site name 1',
         'Site name 2',
         'Site name 3',
         'Site name 4',
       ];
       ```
    1. Configure the list of `Sites that may be searched from this instance` through the module configuration page. You may optionally set this in `settings.php` as well, by setting the `$config['search_api_federated_solr.search_app.settings']['facet']['site_name']['allowed_sites']` variable:
      ```
       $config['search_api_federated_solr.search_app.settings']['facet']['site_name']['allowed_sites'] = [
         'Site name 1',
         'Site name 2',
       ];
       ```
       This example would only allow two of the four sites to be searched from this site. This configuration must be added to every site individually.
1. Index the content for the site using Search API

### Displaying search results

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search-api-federated-solr/search-app/settings`
1. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
1. Optional: [Theme the ReactJS search app](docs/theme.md)
1. Optional: Add the federated search page form block to your site theme + configure the block settings
1. Optional: If you want autocomplete functionality and would prefer that results come from a view, [create a Search API search view with a rest export](https://www.drupal.org/docs/8/modules/search-api/getting-started/search-forms-and-results-pages/searching-with-views-0) or create a content view with a rest export (see the "Search API Federated Solr Block Form Autocomplete" view added as optional config for this module in `config/optional`) and use that url as your autocomplete endpoint.
    1. Under format, choose Solr Serializer as the format (this wraps the view results with the same response object as Solr so they can be rendered)
    1. Under format, choose fields.  Add the title (for Search views, we recommend adding a full text version of your title to the index and adding that instead) and link to content (for Search views, url) fields.
    1. Under format, configure settings for the fields.  Use the alias `ss_federated_title` for your title field and `ss_url` for your url field.
    1. Under Filter Criteria, add those fields you would like to query for the search term as an exposed filter with the "contains any word" operator (for Search views use full text field searches).  For each filter, assign a filter identifier.  These will be used in your autocomplete url as querystring params: `&filter1_identifier_value=[val]&filter2_identifier_value=[val]`.

### About the search proxy

The Drupal integration for Federated Search can operate in `proxy` or `direct` modes. The `proxy` mode is recommended for security and ease-of-use reasons.

When using the proxy, which is enabled by default, all search queries will be run through Drupal's Search API Solr module. Using the proxy is required when using Acquia Search.

If you do not use the proxy, it is your responsibility for securing your Solr server. When using the direct connection method, we recommend that you create a Solr user that only has access to run select queries. The credentials for that user can then be stored as part of the application.

### Adding Solr query debug information to proxy response

To see debug information when using the proxy for your search queries, set `$config['search_api_federated_solr.search_app.settings']['proxy']['debug']` to `TRUE` in your settings.php.

Then user your browsers developer tools to inspect  network traffic.  When your site makes a search query through the proxy, inspect the response for this request and you should now see a `debug` object added to the response object.

*Note: we recommend leaving this set to `FALSE` for production environments, as it could have an impact on performance.*

### Updating the bundled React application

When changes to [federated-search-react](https://github.com/palantirnet/federated-search-react/) are made they'll need to be pulled into this module. To do so:

1. [Publish a release](https://github.com/palantirnet/federated-search-react#publishing-releases) of Federated Search React.
1. Update `search_api_federated_solr.libraries.yml` to reference the new release. Note: You'll need to edit the version number and the hash of both the CSS and JS files.

### More information

Full documentation for this module is in the [handbook on Drupal.org](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-module)

 * [How to configure a Search API Index for federated search](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
 * [How to theme the ReactJS search app](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-8x/theming-the-reactjs-search-app)
 * [Setting up the search page and block](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-module/setting-up-the-search-page)


### Technical notes

The following configuration is a sample that can be used for indexing content. Items marked with a `!` are required.

```
Label             | Machine Name    | Property Path   | Type
-----             | ------------    | -------------   | ----
Federated Title ! | federated_title | mapped_field    | String
Federated Date  ! | federated_date  | mapped_field    | Date
Federated type  ! | federated_type  | mapped_field    | String
Site Name       ! | site_name       | site_name       | String
URLs            ! | urls            | search_api_urls | String
Rendered HTML   ! | rendered_item   | rendered_item   | Fulltext
Federated Image   | federated_image | mapped_field    | String
Federated Terms   | federated_terms | federated_terms | String
```

Items that use `mapped_field` are controlled by Search API Field Map. This module allows you to use strings or tokens to set field values on a per-bundle basis.

For example, Federated Image uses a token specific to an image format supplied by the module: `[node:field_image:search_api_federated_solr_image:url]`

If you prefer to use standard fields like the node title, be sure to rename the machine_name value to match the schema above.

MAINTAINERS
-----------

Current maintainers:
 * Matthew Carmichael (mcarmichael21) - https://www.drupal.org/u/mcarmichael21
 * Jes Constantine (jesconstantine) - https://www.drupal.org/u/jesconstantine
 * Malak Desai (MalakDesai) - https://www.drupal.org/u/malakdesai
 * Byron Duval (byrond) -- https://www.drupal.org/u/byrond
 * Ken Rickard (agentrickard) - https://www.drupal.org/u/agentrickard

This project has been sponsored by:
 * Palantir.net - https://palantir.net
