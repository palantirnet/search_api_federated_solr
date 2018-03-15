## Configuring a Search API index for federated search

Because our data is served by an external application, all sites will need to conform to a set field schema when sending data to the index. Extra fields may be sent for use by individual sites, but they will not be read by the React app.

## Expected schema

* Values in the **Label** column values aren't sent to the index, but are recommended for consistency
* The **Machine Name** and **Type** column values are sent to the index, and must match the table in order for a site's content to be included correctly in results

| Label | Machine Name | Type | Required? | Single/ Multi-value | Description |
| ----- | ------------ | ---- | --------- | -------------------- | ----------- |
| Federated Title | federated_title | string | Yes | single | The title of the item. Displayed as the title of each search result. |
| Federated Date | federated_date | date | No | single | Usually the date the content was created.  Used to provide date-based filtering. |
| Federated Type | federated_type | string | Yes | single | The shared type label for faceting. Also used to label each result. |
| Federated Terms | federated_terms | string | No | multi | Terms for additional, topic-based facets, mapped to shared topic terms if necessary. |
| Federated Image | federated_image | string | No | single | An absolute url to an image which, if it exists, will be displayed with the text. Recommended image size: ___ x ___  |
| Rendered HTML output | rendered_item | fulltext | Yes | multi | The full text of the item, with HTML stripped. |
| URI | url | string | Yes | single | The absolute path to the item, used to provide a link to each result. |
| Site Name | site_name | string | Yes | single | The descriptive name of the source site. Used to provide site-based filtering. |
| Site | site | string | Automatic | single | The base url of the source site, like `https://labblog.uofmhealth.edu`. This will be sent automatically by Drupal, and is required for external content sources. |

### Configuration details

* **Federated Date:** This date must be in `YYYY-MM-DDThh:mm:ssZ` format (as per the [Solr spec](https://lucene.apache.org/solr/guide/6_6/working-with-dates.html)) to be used in date-based facets. Drupal's modules will automatically do this conversion, but other systems may require manual conversion.
* **Federated Type:** This should be mapped to the standard set of types: `Page`, `Patient care`, `Research`, `People`, `News`, `Events`, `Locations`, and `Multimedia`
* **Federated Image:** Do not provide the URL to an original image, since these may be large files. Instead, provide the url to the image with a Drupal Image Style applied. Unlike Drupal, the Federated Search application won't have the ability to resize images before sending them to visitors, and using the full-size file could slow down the results pages for visitors.
* **Rendered HTML output:**
  * Use the `rendered_item` field provided by Search API (found in the index configuration under _Add fields > General > Rendered HTML output_), then select a view mode for each indexed entity type. Often the "Default" view mode will work; otherwise, you may need to create a custom "Search Index" view mode and configure the fields for each indexed entity type and bundle.
    * Make sure that the **Rendered HTML output** includes the title/label for the entity, if the title should be available for full-text search and should impact the relevance of results.
  * Strip HTML from this data by enabling the **HTML filter** in the "Processors" tab of your index configuration.
* **URI:** Use the `search_api_url` field provided by Search API in the index configuration under _Add fields > General > URI_.
* **Site Name:** Use the `site_name` field provided by this module (Search API Federated Solr), found in the index configuration under _Add fields > General > Site Name_.
* **Site:** This will be sent automatically; you do not need to configure a `site` field for your Search API index.

## Example: A site with page, blog post, and user profile content

| Label | Machine Name | Source |
| ----- | ------------ | ------ |
| Federated Title | federated_title | <ul><li>Page, blog post: use the node title</li><li>Profile: use the content's "Display Name" field.</li></ul> |
| Federated Date | federated_date | <ul><li>Page, profile: no value</li><li>Blog post: publishing date</li></ul> |
| Federated Type | federated_type | <ul><li>Page: `Page`</li><li>Blog post: `News`</li><li>Profile: `People`</li></ul> |
| Federated Terms | federated_terms | <ul><li>Page: no value</li><li>Blog post: tags, mapped to shared terms</li><li>Profile: specialties tags, mapped to shared terms</li></ul> |
| Federated Image | federated_image | <ul><li>Page, blog post: use the content's "Featured Image" field.</li><li>Profile: use the content's "Profile Photo" field.</li></ul> |
| Rendered HTML output | rendered_item | <ul><li>Page, blog post, profile: render using "Default" view mode</li></ul> |
| URI | url | Use Search API's "URI" index field. |
| Site Name | site_name | Use this (Search API Federated Solr) module's "Site Name" index field. |
