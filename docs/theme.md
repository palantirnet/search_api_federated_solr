## How to theme the ReactJS search app

1. Theming the search app in the context of your Drupal site:
    1. For themes with SASS: Copy `./docs/assets/search_theme_override.scss` from this module and add it to your theme sass files and start making changes.
    1. For themes with CSS only: Copy `./docs/assets/search_theme_override.css` from this module and add it to your theme css files and start making changes.
    1. You'll likely also need to [define this css file as a theme library and attach it to the search page](#adding-the-styles-to-your-theme)  

### Adding the styles to your theme
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

### Notes
The Sass/CSS assets that are included in docs/assets are examples only. They will not be regularly maintained or updated.
