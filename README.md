# Federated Solr Search API Module

SearchAPI Federated Solr is an open source alternative to the Google Search Appliance and other technologies that index and search across multiple web sites.

The module facilitates indexing data from multiple Drupal 7 and 8 sites into a single Solr search index.  The module provides a ReactJS front end that presents uniform search results across the different sites.

Primary features of the module include:

- Indexing of multiple, independent Drupal sites into a single index
Optional filtering of search results by site
- Standard presentation of search results on all sites
- A standard search block for use on all sites
- Customizable presentation using a single CSS file

## How does it work?

Searching across multiple sites requires a common set of data. By design, SearchAPI Federated Solr indexes the following information for each item in the search index:

- Title
- Content Creation Date
- Content Type
- Content Tags (all taxonomy terms)
- Preview Image, if available
- Full Text
- URI
- Site Name
- Site Base URI

These nine data points represent the most common requirements in search results. They allow for uniform searching and presentation and for the faceting of results.

Because our data is served by an external application, all sites will need to conform to a set field schema when sending data to the index. Extra fields may be sent for use by individual sites, but they will not be read by the React application.

On each site included in the federated search, you will need to:

<<<<<<< HEAD
1. Instal the Fields Search API module
2. Install this module and its dependencies
3. Configure a Search API server to connect to the shared Solr index
4. Configure a Search API index according to the [required schema documentation](docs/federated_schema.md)
5. Index the content for the site using SearchAPI

Once each site is configured, you may begin to index content.

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search-api-federated-solr/search-app/settings`
2. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
3. Optional: [Theme the ReactJS search app](docs/theme.md)
4. Optional: Add the federated search page form block to your site theme

## Module Dependencies

SearchAPI Federated Solr requires the following standard modules:

- SearchAPI
- SearchAPI Field Map
- SeachAPI Solr
- Token

The SearchAPI Field Map module is used to provide common indexing across each site. See that module’s documentation for additional information.

The module also relies on the Federated Search React application, which can be included as a Drupal library.


## Updating the bundled React application

When changes to [federated-search-react](https://github.com/palantirnet/federated-search-react/) are made they'll need to be pulled into this module. To do so:

- Update `package.json` with the new version.
- Run `yarn install`
- Delete the old versions of `js/main.*.js (and .map)` and `css/main.*.css (and .map)`
- Update `search_api_federated_solr.libraries.yml` to reference the new file.

## More information

* [How to configure a Search API Index for federated search](docs/federated_schema.md)
* [How to theme the ReactJS search app](docs/theme.md)
* [How to add the search form block](docs/block.md)
