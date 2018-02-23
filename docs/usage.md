## About this module

This module provides a "Federated field" on Search API indexes, which can be used to aggregate data from different entity types into the same field in the search index.

This is similar to the "Aggregated field" provided by Search API, but gives more direct, token-based control over the values for different entity types.

## Using the "Federated field"

1. Visit the fields list for your index at _Admin > Configuration > Search API > [your index] > Fields_ (path `/admin/config/search/search-api/index/YOUR_INDEX/fields`)
2. Click "Add fields"
3. Click the "Add" button for the "Federated Field":

  <img src="images/add_federated_field.png" />
4. Configure field data for each entity type. This field allows token replacement; enter plain text directly or use the token browser to select tokens.

  <img src="images/edit_federated_field.png" />
5. Save your field.
6. Edit the field label, machine name, and type as necessary for your data

## Expected schema

Because our data is served by an external application, all sites will need to conform to a set field schema when sending data to the index. Extra fields may be sent for use by individual sites, but they will not be read by the React app.

`Label` isn't sent to the index, but is recommended for consistency.

| Label | Machine Name | Type | Example | Description |
| ----- | ------------ | ---- | ------- | ----------- |
| Federated Title | federated_title | string | The title of the item. |
| Federated Date | federated_date | date | Usually the date the content was created. |
| Federated Type | federated_type | string | The content type or other descriptor for faceting. |
| Federated Terms | federated_terms | string | Terms to facet on. |
| Federated Image | federated_image | string | An absolute url to an image which, if it exists, will be displayed with the text. Recommended image size: ___ x ___ |
| Rendered HTML output | rendered_item | fulltext | The rendered item, with HTML stripped. |
| URI | url | string | The absolute path to the item. |
| Site Name | site_name | string | The descriptive name of the source site. |
| Site | site | string | The base url of the source site, like `https://labblog.uofmhealth.edu`. This will be sent automatically by Drupal, but is required for eternal content sources.

