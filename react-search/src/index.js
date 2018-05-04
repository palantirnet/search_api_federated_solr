// index.js
import queryString from "query-string";
import React from "react";
import ReactDOM from "react-dom";
import { SolrClient } from "./solr-faceted-search-react/src/index";
import FederatedSolrComponentPack from "./components/federated_solr_component_pack";
import FederatedSolrFacetedSearch from "./components/federated-solr-faceted-search";

// import search app boilerplate styles
import './styles.css';

// The search fields and filterable facets you want
const fields = [
  {label: "Enter Search Term:", field: "tm_rendered_item", type: "text"},
  {label: "Site Name", field: "ss_site_name", type: "list-facet", collapse: true},
  {label: "Type", field: "ss_federated_type", type: "list-facet", collapse: true},
  {label: "Date", field: "ds_federated_date", type: "range-facet", collapse: true},
  {label: "Federated Terms", field: "sm_federated_terms", type: "list-facet", hierarchy: true},
];

// The solr field to use as the source for the main query param "q"
const mainQueryField = "tm_rendered_item";

// The sortable fields you want
const sortFields = [
  {label: "Relevance", field: "score"},
  {label: "Date", field: "ds_federated_date"}
];

// Enable highlighting in search results snippets
const highlight = {
  fl: 'tm_rendered_item', // the highlight snippet source field(s)
  usePhraseHighlighter: true // highlight phrase queries
};

// @todo get the configurable options, if any, from drupal config
// this should probably be an options object so that we can only add one global prop
// const searchSite = 'University of Michigan Health Blog'; // uncomment to test
const searchSite = null;

/**
 * Executes search query based on the value of URL querystring "q" param.
 *
 * @param solrClient
 *   Instantiated solrClient.
 */
const searchFromQuerystring = (solrClient) => {
  // Initialize with a search based on querystring term or else load blank search.
  const parsed = queryString.parse(window.location.search);
  // We assume the querystring key for search terms is q: i.e. ?q=search%20term
  if (Object.prototype.hasOwnProperty.call(parsed,'q')) {
    solrClient.setSearchFieldValue("tm_rendered_item", parsed.q);
  }
  // Reset search fields, fetches all results from solr. Note: results will be hidden
  // since there is no search term.  See: federated-solr-faceted-search where
  // ResultContainerComponent is rendered.
  else {
    solrClient.resetSearchFields();
  }
};

document.addEventListener("DOMContentLoaded", () => {
// The client class
  const solrClient = new SolrClient({
// The solr index url to be queried by the client
    url: "https://ss826806-us-east-1-aws.measuredsearch.com:443/solr/master/select",
    searchFields: fields,
    sortFields: sortFields,
    pageStrategy: "paginate",
    rows: 20,
    hl: highlight,
    mainQueryField: mainQueryField,

// The change handler passes the current query- and result state for render
// as well as the default handlers for interaction with the search component
    onChange: (state, handlers) =>
// Render the faceted search component
        ReactDOM.render(
            <FederatedSolrFacetedSearch
                {...state}
                {...handlers}
                customComponents={FederatedSolrComponentPack}
                bootstrapCss={false}
                onSelectDoc={(doc) => console.log(doc)}
                truncateFacetListsAt={-1}
                searchSite={searchSite}
            />,
            document.getElementById("root")
        )
  });

  // Check if there is a querystring param search term and make initial query.
  searchFromQuerystring(solrClient);

  // Listen for browser history changes and update querystring, make new query as necessary.
  // See https://developer.mozilla.org/en-US/docs/Web/Events/popstate
  window.onpopstate = function() {
    searchFromQuerystring(solrClient);
  };
});
