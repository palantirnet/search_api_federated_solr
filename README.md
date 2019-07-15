INTRODUCTION
------------

Search API Federated Solr is an open source alternative to the Google Search Appliance and other technologies that index and search across multiple web sites.

The module facilitates indexing data from multiple Drupal 7 and 8 sites into a single Solr search index.  The module provides a ReactJS front end that presents uniform search results across the different sites.

Primary features of the module include:

 * Indexing of multiple, independent Drupal sites into a single index
 * Optional filtering of search results by site
 * Standard presentation of search results on all sites
 * A standard search block for use on all sites with optional configurable type-ahead search results
 * Customizable presentation using a single CSS file

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


REQUIREMENTS
------------

Search API Federated Solr requires the following modules:

 * Search API (https://www.drupal.org/project/search_api)
 * Search API Field Map (https://github.com/palantirnet/search_api_field_map)
 * SeachAPI Solr (https://www.drupal.org/project/search_api_solr) version 8.x-1.x, versions 8.x-2.x and newer not tested
 * Token (https://www.drupal.org/project/token)

The Search API Field Map module is used to provide common indexing across each site. See that module’s documentation for additional information.

The module also relies on the [Federated Search React](https://github.com/palantirnet/federated-search-react) application, which is referenced as an external Drupal library.

Apache Solr versions `4.5.1` and `5.x` have been used with this module and it is likely that newer versions will also work.

INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

On each site included in the federated search, you will need to:

1. Install this module and its dependencies
1. Configure a Search API server to connect to the shared Solr index
1. Configure a Search API index according to the [required schema documentation](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
    1. Optional: To help facilitate autocomplete term partial queries, consider adding a Fulltext [Edge Ngram](https://lucene.apache.org/solr/guide/6_6/tokenizers.html) version of your title field to the index (See [example](https://github.com/palantirnet/federated-search-demo/blob/master/config/sites/d8/search_api.index.federated_search_index.yml#L86) in the Federated Search Demo site Solr index config).  Also consider adding that field as a default query field for your Solr server's default Request Handler.
    1. Optional: If your site uses a "search terms" or similar field to trigger a boost for items based on given search terms, consider adding a Fulltext [Edge Ngram](https://lucene.apache.org/solr/guide/6_6/tokenizers.html) version of that field to the index.  Also consider adding that field as a default query field for your Solr server's default Request Handler.
1. Optional: Configure default fields for queries.  The default query field for search queries made through the proxy provided by this module is the `rendered_item` field.  To set a different value for the default query fields there are two options:
    1. Set `$config['search_api_federated_solr.search_app.settings']['index']['query_fields']` to an array of _Fulltext_ field machine names (i.e. `['rendered_item', 'full_text_title']`) from your search index in `settings.php`.
        - This method will not work if you disable the proxy that this module provides for querying your solr backend in the search app or block autocomplete settings
        - By default, the proxy will validate the field names to ensure that they are full text and that they exist on the index for this site.  Then it will translate the index field name into its solr field name counterpart.  If you need to disable this validation + transformation (for example to search fields on a D7 site index whose machine names are different than the D8 site counterpart), set `$config['search_api_federated_solr.search_app.settings']['index']['validate_query_fields']` to `FALSE`.  Then you must supply the _solr field names_.  To determine what these field names are on your D8 site, use the drush command `drush sapifs-f`, which will output a table with index field names and their solr field name counterparts.
    1. Configure that Search API server to set default query fields for your default [Request Handler](https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-SearchHandlers). (See [example](https://github.com/palantirnet/federated-search-demo/blob/master/conf/solr/drupal8/custom/solr-conf/4.x/solrconfig_extra.xml#L94) in Federated Search Demo site Solr server config)
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

Once each site is configured, you may begin to index content.

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
