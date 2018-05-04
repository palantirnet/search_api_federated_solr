# Federated Solr Search API Module

This module facilitates indexing data from multiple Drupal sites into a single Solr search index.  It also provides a  ReactJS front end to render search results from that Solr search index.

On each site included in the federated search, you will need to:

1. Install this module
2. Configure a Search API server to connect to the shared Solr index
3. Configure a Search API index according to the [recommended schema](docs/federated_schema.md)
4. Optional: Theme the ReactJS search app
5. Optional: Add the federated search page form block to your site theme

## More information

* [How to use this module](docs/usage.md)
* [How to configure a Search API Index for federated search](docs/federated_schema.md)
* [How to theme the ReactJS search app](docs/theme.md)
* [How to add the search form block](docs/block.md)
