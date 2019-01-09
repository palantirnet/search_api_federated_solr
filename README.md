INTRODUCTION
------------

Search API Federated Solr is an open source alternative to the Google Search Appliance and other technologies that index and search across multiple web sites.

The module facilitates indexing data from multiple Drupal 7 and 8 sites into a single Solr search index.  The module provides a ReactJS front end that presents uniform search results across the different sites.

Primary features of the module include:

 * Indexing of multiple, independent Drupal sites into a single index
 * Optional filtering of search results by site
 * Standard presentation of search results on all sites
 * A standard search block for use on all sites
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
 * SeachAPI Solr (https://www.drupal.org/project/search_api_solr)
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
    2. Configure a Search API server to connect to the shared Solr index
    3. Configure a Search API index according to the [required schema documentation](docs/federated_schema.md)
    4. Index the content for the site using Search API

Once each site is configured, you may begin to index content.

In order to display results from the Solr index:

    1. Configure the application route and settings at `/admin/config/search-api-federated-solr/search-app/settings`
    2. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
    3. Optional: [Theme the ReactJS search app](docs/theme.md)
    4. Optional: Add the federated search page form block to your site theme


### Updating the bundled React application

When changes to [federated-search-react](https://github.com/palantirnet/federated-search-react/) are made they'll need to be pulled into this module. To do so:

    1. [Publish a release](https://github.com/palantirnet/federated-search-react#publishing-releases) of Federated Search React.
    2. Update `search_api_federated_solr.libraries.yml` to reference the new release. Note: You'll need to edit the version number and the hash of both the CSS and JS files.

### More information

Full documentation for this module is in the [handbook on Drupal.org](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-module)

 * [How to configure a Search API Index for federated search](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
 * [How to theme the ReactJS search app](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-8x/theming-the-reactjs-search-app)
 * [Setting up the search page and block](https://www.drupal.org/docs/8/modules/search-api-federated-solr/search-api-federated-solr-module/setting-up-the-search-page)


MAINTAINERS
-----------

Current maintainers:
 * Avi Schwab (froboy) - https://www.drupal.org/u/froboy
 * Ken Rickard (agentrickard) - https://www.drupal.org/u/agentrickard
 * Malak Desai (MalakDesai) - https://www.drupal.org/u/malakdesai
 * Matthew Carmichael (mcarmichael21) - https://www.drupal.org/u/mcarmichael21

This project has been sponsored by:
 * Palantir.net - https://palantir.net
