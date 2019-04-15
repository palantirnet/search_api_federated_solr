# Search API Federated Solr Module

This module facilitates indexing data from multiple Drupal sites into a single Solr search index.

On each site included in the federated search, you will need to:

1. Install this module
2. Configure a Search API server to connect to the shared Solr index
3. Configure a Search API index according to the [recommended schema](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search/federated-search-settings`
2. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
3. Optional: [Theme the ReactJS search app](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/theming-the-reactjs-search)
4. Optional: Add the federated search page form block to your site theme

## Updating the bundled React application

When changes to [federated-search-react](https://github.com/palantirnet/federated-search-react/) are made they'll need to be pulled into this module. To do so:

1. [Publish a release](https://github.com/palantirnet/federated-search-react#publishing-releases) of Federated Search React.
2. Update `search_api_federated_solr_library()` in `search_api_federated_solr.module` to reference the new release. Note: You'll need to edit the version number and the hash of both the CSS and JS files.

## More information

Full documentation for this module is available in the [handbook on Drupal.org](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module)

* [How to use this module](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/intro-install-configure)
* [How to configure a Search API Index for federated search](https://www.drupal.org/docs/8/modules/search-api-federated-solr/federated-search-schema)
* [How to theme the ReactJS search app](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/theming-the-reactjs-search)
* [Setting up the search page and block](https://www.drupal.org/docs/7/modules/search-api-federated-solr/search-api-federated-solr-module/setting-up-the-search-page)
