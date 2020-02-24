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

### Upgrading from 8.x-2.x to 8.x-3.x
Starting on version 3.x of the module, the CSS has been updated to a new namespacing. All CSS classes are now updated to have a "fs-" prefix and all styled are applied within the main app ID of `#fs-root`, so any previously written CSS or SCSS overrides for version 2.x will need to be updated to reflect this namespace change. .  

## Theming the Drupal elements

While the React application is separate from Drupal, the provided search block `Federated Search Page Form block` is themable, as is the wrapper around the search results page. There are three theme files.

### search-app.html.twig

This template instantiates the application itself. It is designed to use the full width of the page. If you override this template in your theme, you must retain the root div element:

`<div id="fs-root" data-federated-search-app-config="{{ app_config }}">`

Without this element, the application will not function.

### search-api-federated-solr-block.html.twig

This template displays the search block, with no block title, as a suitable replacement for the core search block.

### search-api-federated-solr-block-form.html.twig

This template generates the search form that appears in the block. You may override its elements by printing them individually. When doing so, be sure to render the hidden form elements.
