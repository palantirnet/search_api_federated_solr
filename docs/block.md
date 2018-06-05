## How to add the search form block

You can add the provided search form block by browsing to your sites's block placement page at `/admin/structure/block`.  Scroll down to the disabled blocks and find the "Federated Search Page Form block".  

Place the block in your desired theme region.

Configure the block to hide the block title.

### Using your own search form

If you use your own search form, make sure to configure it to work with the Federated Search App search page:

* Build a URL from the `search_api_federated_solr_path` variable and set that as the form action
* Use `get` as the form method
* Ensure that the querystring param for the search term is named `search`
