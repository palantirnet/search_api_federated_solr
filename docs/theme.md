## How to theme the ReactJS search app

You can theme the search app by copying either the provided starter `SCSS` or `CSS` file into your theme styles (see the `/docs/assets` directory in this module).

Once you have defined your theme styles, we recommend creating a theme library with the `CSS` and attaching that file to the search page route.  See examples below.

### Example theme library definition
```yaml
# <your-theme>/<your-theme>.libraries.yml file
# =============================================

# Define search theme library
search-theme:
  css:
    theme:
      css/search_override.css: {}

```

### Example library attachment
```php
// <your-theme>/<your-theme>.theme file
// =====================================


/**
 * Implements hook_page_attachments_alter().
 */
function <your-theme>_page_attachments_alter(array &$page) {

  // Add the search style overrides to the search results page.
  $route_match = \Drupal::routeMatch();
  if ($route_match->getRouteName() === 'search_api_federated_solr.search') {
    $page['#attached']['library'][] = '<your-theme>/search-theme';
  }
}
```
