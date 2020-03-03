# Search API Federated Solr Module

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

Search API Federated Solr 7.x-3.x requires the following modules:

 * Search API (https://www.drupal.org/project/search_api)
 * SeachAPI Solr (https://www.drupal.org/project/search_api_solr) version 7.x-1.x
 * Token (https://www.drupal.org/project/token)

The module also relies on the [Federated Search React](https://github.com/palantirnet/federated-search-react) application, which is referenced as an external Drupal library.

Apache Solr versions `7.7.2` have been used with this module and it is likely that newer versions will also work.

### Older Versions

The 7.x-2.x version of this module supports SearchAPI Solr 7.x-1.x and Solr 4.5 - 6.x.

INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information. The module needs to be installed and configured for every site in your index.

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


On each site included in the federated search, you will need to:

1. Install this module
2. Configure a Search API server to connect to the shared Solr index
3. Configure a Search API index according to the [recommended schema](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search/federated-search-settings`
1. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
1. Optional: Configure default fields for queries.  The default query field for search queries made through the proxy provided by this module is the `rendered_item` field.  To set a different value for the default query fields there are two options:
    1. Set `$conf['search_api_federated_solr_proxy_query_fields']` to an array of _Fulltext_ field machine names (i.e. `['tm_rendered_item', 'full_text_title']`) from your search index in `settings.php`.
        - This method will not work if you disable the proxy that this module provides for querying your solr backend in the search app or block autocomplete settings
        - By default, the proxy will validate the field names to ensure that they are full text and that they exist on the index for this site.  Then it will translate the index field name into its solr field name counterpart.  If you need to disable this validation + transformation (for example to search fields on a D8 site index whose machine names are different than the D7 site counterpart), set `$conf['search_api_federated_solr_proxy_validate_query_fields_against_schema']` to `FALSE`.  Then you must supply the _solr field names_.  To determine what these field names are on your D7 site, use the drush command `drush sapifs-f`, which will output a table with index field names and their solr field name counterparts.
    1. Configure that Search API server to set default query fields for your default [Request Handler](https://lucene.apache.org/solr/guide/6_6/requesthandlers-and-searchcomponents-in-solrconfig.html#RequestHandlersandSearchComponentsinSolrConfig-SearchHandlers). (See [example](https://github.com/palantirnet/federated-search-demo/blob/solr-7/conf/solr/drupal8/custom/solr-conf/7.x/solrconfig_extra.xml) in the Federated Search Demo site Solr server config.)
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

1. Index the content for the site using Search API

### Displaying search results

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search/federated-search-settings`
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

## Adding Solr query debug information to proxy response

To see debug information when using the proxy for your search queries, set `$conf['search_api_federated_solr_proxy_debug_query']` to `TRUE` in your settings.php.

Then user your browsers developer tools to inspect  network traffic.  When your site makes a search query through the proxy, inspect the response for this request and you should now see a `debug` object added to the response object.

*Note: we recommend leaving this set to `FALSE` for production environments, as it could have an impact on performance.*

### Technical notes

The following configuration is a sample that can be used for indexing content. Items marked with a `!` are required.

```
Label             | Machine Name    | Type
-----             | ------------    | ----
Federated Title ! | federated_title | String
Federated Date  ! | federated_date  | Date
Federated type  ! | federated_type  | String
Site Name       ! | site_name       | String
URLs            ! | urls            | URI
Entity HTML     ! | rendered_item   | Fulltext
Federated Image   | federated_image | String
Federated Terms   | federated_terms | String
```

In Drupal 7, use the Filters settings for your search index to control these fields. You will need to enable the `Site Name`, `URLs`, `Federated Field`, and `Re-map Field Names` filters. The `Federated Term` filter is optional.

### Site Name filter

The name of the site from which this content originated. Used for indexing multiple sites with a single search index. This field is configurable. If left blank, the default Site Name system variable will be used.

### URLs filter

The links to the node on all available sites. Used for indexing multiple sites with a single search index. If you are using Domain Access, you should also enable the `Canonical URL` filter. These filters are not configurable.

### Federated Field filter

This allows you to add new fields that allow string or token settings. This feature is especially useful for the Federated Image field, which uses a token such as `[node:field_image:search_api_federated_solr_image]`.

### Re-map Field Names filter

For cases where Search API already exposes the data you wish to index, you can use this filter to remap specific items to the fields used by Federated Search. The following fields can be remapped:

- Federated Title
- Federated Date
- Federated Type
- Federated Terms
- Federated Image
- Rendered Item

Note: In Drupal 8, this functionality is provided by the Search API Field Map module.

### Indexed item sample

The record below is a JSON representation of a Drupal article indexed from Drupal 7.

```
"id":"4sckok-federated_search-10",
"index_id":"federated_search",
"item_id":"10",
"hash":"4sckok",
"site":"http://d7.fs-demo.local/",
"ds_federated_date":"2020-01-21T01:48:40Z",
"sm_federated_terms":["Age>Adult",
  "Color>Black",
  "Traits>Athletic",
  "Traits>Energetic"],
"ss_federated_title":"Akita",
"ss_federated_type":"Page",
"tm_rendered_item":["Akita",
  "Chew toy bang leap, kong lab down dog toy puppies. Dog toy down bang leash, sit bark spin tennis ball. dog toy peanut butter leave it dog, dog house stand jump bang bark. Adult Athletic Black"],
"spell":["Akita",
  "Chew toy bang leap, kong lab down dog toy puppies. Dog toy down bang leash, sit bark spin tennis ball. dog toy peanut butter leave it dog, dog house stand jump bang bark. Adult Athletic Black",
  "Akita Chew toy bang leap, kong lab down dog toy puppies. Dog toy down bang leash, sit bark spin tennis ball. dog toy peanut butter leave it dog, dog house stand jump bang bark. Adult Athletic Black"],
"ss_search_api_language":"und",
"sm_site_name":["Search Drupal 7"],
"sm_urls":["http://d7.fs-demo.local/node/10"],
"content":"Akita Chew toy bang leap, kong lab down dog toy puppies. Dog toy down bang leash, sit bark spin tennis ball. dog toy peanut butter leave it dog, dog house stand jump bang bark. Adult Athletic Black",
"_version_":1660176245253472256,
"timestamp":"2020-03-03T20:30:45.787Z"}]
```

For additional technical resources, you can see the [Federated Search Demo project](https://github.com/palantirnet/federated-search-demo).

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
