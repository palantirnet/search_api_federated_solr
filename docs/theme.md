## How to theme the ReactJS search app

There are many ways to create a custom search app theme.  The two that we recommend are:
1. Cloning the search app repo and working in the development environment (perhaps faster, especially if you're familiar/comfortable with things like `git` and `yarn`)
    1. Clone `https://github.com/palantirnet/federated-search-react`
    1. Move into the repo root `cd federated-search-reach`
    1. Install dependencies `yarn install`
    1. Configure your solr backend
        1. Copy `./src/.env.local.js.example` into `./src/.env.local.js`
        1. Configure your solr backend url in `./src/.env.local.js`
    1. Spin up the dev instance `yarn start`
    1. Open the project in your IDE or code editor
    1. Start making changes to `./theme/search_theme_override` (Protip: make sure you uncomment any of the changes you make by removing the beginning `//`)
    1. Save your changes and go back to the search app in your browser, the app should reload each time you press save
    1. Once you've got the app themed, you can either use `./theme/search_theme_override.scss` in your site theme `sass` workflow or grab the `./public/css/search_theme_override.css` file and [add it to your theme styles](#adding-the-styles-to-your-theme)
1. Theming the search app in the context of your Drupal site (perhaps longer, but perfectly okay, especially if your site has a scss/css workflow that you're comfortable with)
    1. For themes with SASS: Copy `./docs/assets/search_theme_override.scss` from this module and add it to your theme sass files and start making changes.
    1. For themes with CSS only: Copy `./docs/assets/search_theme_override.css` from this module and add it to your theme css files and start making changes.
    1. You'll likely also need to [define this css file as a theme library and attach it to the search page](#adding-the-styles-to-your-theme)  

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
