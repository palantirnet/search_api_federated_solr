# Federated Solr Search API Module

This module facilitates indexing data from multiple Drupal sites into a single Solr search index.  It also provides a  ReactJS front end to render search results from that Solr search index.

On each site included in the federated search, you will need to:

1. Install Fields Search API Module
2. Install this module
3. Configure a Search API server to connect to the shared Solr index
4. Configure a Search API index according to the [recommended schema](docs/federated_schema.md)

In order to display results from the Solr index:

1. Configure the application route and settings at `/admin/config/search-api-federated-solr/search-app/settings`
2. Set permissions for `Use Federated Search` and `Administer Federated Search` for the proper roles.
3. Optional: [Theme the ReactJS search app](docs/theme.md)
4. Optional: Add the federated search page form block to your site theme

## Updating the bundled React application

When changes to [federated-search-react](https://github.com/palantirnet/federated-search-react/) are made they'll need to be pulled into this module. To do so:

- Update `package.json` with the new version.
- Run `yarn install`
- Delete the old versions of `js/main.*.js (and .map)` and `css/main.*.css (and .map)`
- Update `search_api_federated_solr.libraries.yml` to reference the new file.

## More information

* [How to theme the ReactJS search app](docs/theme.md)
* [How to add the search form block](docs/block.md)
