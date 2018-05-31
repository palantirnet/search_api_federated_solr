## How to theme the ReactJS search app

There are many ways to create a custom search app theme.  The two that we recommend are:
1. Cloning the search app repo and working in the development environment (perhaps faster, especially if you're familiar/comfortable with things like `git` and `yarn`)
    1. Clone `https://github.com/palantirnet/federated-search-react`
    1. Move into the repo root `cd federated-search-reach`
    1. Install dependencies `yarn install`
    1. Configure your solr backend
        1. Copy `./src/.env.js.example` into `./src/.env.js`
        1. Configure your solr backend url in `./src/.env.js`
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
