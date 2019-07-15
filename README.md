# Search API Federated Solr Module

This module facilitates indexing data from multiple Drupal sites into a single Solr search index.

On each site included in the federated search, you will need to:

1. Install this module
2. Configure a Search API server to connect to the shared Solr index
3. Configure a Search API index according to the [recommended schema](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search/federated-search-settings`
1. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
1. Optional: Configure default fields for queries.  The default query field for search queries made through the proxy provided by this module is the `rendered_item` field.  To set a different value for the default query fields there are two options:
    1. Set `$conf['search_api_federated_solr_proxy_query_fields']` to an array of _Fulltext_ field machine names (i.e. `['rendered_item', 'full_text_title']`) from your search index in `settings.php`.
        - This method will not work if you disable the proxy that this module provides for querying your solr backend in the search app or block autocomplete settings
        - By default, the proxy will validate the field names to ensure that they are full text and that they exist on the index for this site.  Then it will translate the index field name into its solr field name counterpart.  If you need to disable this validation + transformation (for example to search fields on a D8 site index whose machine names are different than the D7 site counterpart), set `$conf['search_api_federated_solr_proxy_validate_query_fields_against_schema']` to `FALSE`.  Then you must supply the _solr field names_.  To determine what these field names are on your D7 site, use the drush command `drush sapifs-f`, which will output a table with index field names and their solr field name counterparts.
    1. Configure that Search API server to set default query fields for your default [Request Handler](https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-SearchHandlers). (See [example](https://github.com/palantirnet/federated-search-demo/blob/master/conf/solr/drupal8/custom/solr-conf/4.x/solrconfig_extra.xml#L94) in Federated Search Demo site Solr server config)
1. Optional: Configure a list of sites that you wish to search from this instance. You can restrict the list of sites to search by adding configuration to your `settings.php` file.
    1. Set `$conf['search_api_federated_solr_site_list']` to an array of site name for your sites. This can normally be left blank if you wish to search all sites in your installed cluster. The array should normally include all sites in your cluster and be in the format:
       ```
       $conf['search_api_federated_solr_site_list'] = [
         'Site name 1',
         'Site name 2',
         'Site name 3',
         'Site name 4',
       ];
       ```

    1. Configure the list of `Sites that may be searched from this instance` through the module configuration page. You may optionally set this in `settings.php` as well, by setting the `$config['search_api_federated_solr_allowed_sites']` variable:
      ```
       $conf['search_api_federated_solr_allowed_sites'] = [
         'Site name 1',
         'Site name 2',
       ];
       ```

       This example would only allow two of the four sites to be searched from this site. This configuration must be added to every site individually.
1. Optional: [Theme the ReactJS search app](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/theming-the-reactjs-search)
1. Optional: Add the federated search page form block to your site theme

## Adding Solr query debug information to proxy response

To see debug information when using the proxy for your search queries, set `$conf['search_api_federated_solr_proxy_debug_query']` to `TRUE` in your settings.php.

Then user your browsers developer tools to inspect  network traffic.  When your site makes a search query through the proxy, inspect the response for this request and you should now see a `debug` object added to the response object.

*Note: we recommend leaving this set to `FALSE` for production environments, as it could have an impact on performance.*

## Requirements

Search API Federated Solr requires the following modules:

 * Search API (https://www.drupal.org/project/search_api) version 7.x-1.x
 * SeachAPI Solr (https://www.drupal.org/project/search_api_solr) version 7.x-1.x

The module also relies on the [Federated Search React](https://github.com/palantirnet/federated-search-react) application, which is referenced as an external Drupal library.

Apache Solr versions `4.5.1`, `5.x`, and `6.x` have been used with this module and it is likely that newer versions will also work.

## More information

Full documentation for this module is available in the [handbook on Drupal.org](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module)

* [How to use this module](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/intro-install-configure)
* [How to configure a Search API Index for federated search](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
* [How to theme the ReactJS search app](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/theming-the-reactjs-search)
* [Setting up the search page and block](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/setting-up-the-search-page)

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
