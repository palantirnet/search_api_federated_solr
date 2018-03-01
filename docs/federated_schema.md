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

