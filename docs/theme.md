## How to theme the ReactJS search app

1. Theming the search app in the context of your Drupal site:
    1. For themes with SASS: Copy `./docs/assets/search_theme_override.scss` from this module and add it to your theme sass files and start making changes.
    1. For themes with CSS only: Copy `./docs/assets/search_theme_override.css` from this module and add it to your theme css files and start making changes.
    1. You'll likely also need to [add your css to template.php for your theme](#adding-the-styles-to-your-theme)

### Adding the styles to your theme
Once you have defined your theme styles, we recommend adding the `CSS` to your theme directory and attaching that file to the search page route.

Assuming that your search theme css file exists at <your-theme>/css/search_theme_override.css, you can update your theme `template.php` with:

```php
// <your-theme>/template.php file
// =====================================


/**
 * Override or insert variables into the page templates.
 *
 * @param $variables
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("page" in this case.)
 */

function <your-theme>_preprocess_page(&$variables, $hook) {

  // Add search theme override css to the search app path.
  $path = current_path();
  $search_path = variable_get('search_api_federated_solr_path', 'search-app');

  if ($search_path && $search_path === $path) {
    drupal_add_css(drupal_get_path('theme', '<your-theme>') . '/css/search_theme_override.css', array('group' => CSS_THEME));
  }
}
```

### Notes
The Sass/CSS assets that are included in docs/assets are examples only. They will not be regularly maintained or updated.

### Upgrading from 7.x-2.x to 7.x-3.x
Starting on version 3.x of the module, the CSS has been updated to a new namespacing. All CSS classes are now updated to have a "fs-" prefix and all base styles are applied within the main app ID of `#fs-root`, so any previously written CSS or SCSS overrides for version 2.x will need to be updated to reflect this namespace change.
