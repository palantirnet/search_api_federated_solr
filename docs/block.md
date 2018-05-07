## How to add the search form block

You can add the provided search form block by browsing to your sites's block placement page at `/admin/structure/block`.  Place a block in your desired theme region.

In the "Place block" dialogue, select the "Federated Search Page Form block" in the "Federated Search" category.

Configure the block to hide the block title.

### Using your own search form

If you use your own search form, make sure to configure it to work with the Federated Search App search page:

* Build a URL from the `search_api_federated_solr.search` route and set that as the form action
* Use `get` as the form method
* Ensure that the querystring param for the search term is named `q`
