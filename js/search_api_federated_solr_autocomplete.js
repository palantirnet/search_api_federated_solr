/**
 * @file
 * Adds autocomplete functionality to search_api_solr_federated block form.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  var autocomplete = {};

  /**
   * Attaches our custom autocomplete settings to the search_api_federated_solr block search form field.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   */
  Drupal.behaviors.searchApiFederatedSolrAutocomplete = {
    attach: function (context, settings) {
      // Find our fields with autocomplete settings
      $(context)
        .find('.js-search-api-federated-solr-block-form-autocomplete #edit-search')
        .once('search-api-federated-solr-autocomplete-search')
        .each(function () {
          // Halt execution if we don't have the required config.
          if (!Object.hasOwnProperty.call(drupalSettings, 'searchApiFederatedSolr')
              || !Object.hasOwnProperty.call(drupalSettings.searchApiFederatedSolr, 'block')
              || !Object.hasOwnProperty.call(drupalSettings.searchApiFederatedSolr.block, 'autocomplete')
              || !Object.hasOwnProperty.call(drupalSettings.searchApiFederatedSolr.block.autocomplete, 'url')) {
            return;
          }

          // Set default settings.
          var defaultSettings = {
            isEnabled: false,
            appendWildcard: false,
            userpass: '',
            numChars: 2,
            suggestionRows: 5,
            mode: 'result',
            result: {
              titleText: "What are you looking for?",
              hideDirectionsText: 0
            }
          };
          // Get passed in config from block config.
          var config = drupalSettings.searchApiFederatedSolr.block.autocomplete;
          // Merge defaults with passed in config.
          var options = Object.assign({}, defaultSettings, config);

          // Set scaffolding markup for suggestions container
          var suggestionsContainerScaffoldingMarkup = `
            <div class="js-search-autocomplete-container search-autocomplete-container visually-hidden">
              <div class="search-autocomplete-container__title">
                 ${options[options.mode].titleText}
                 <button class="js-search-autocomplete-container__close-button search-autocomplete-container__close-button">x</button>
               </div>
               <div id="js-search-autocomplete search-autocomplete">
                <div id="res" role="listbox" tabindex="-1"></div>
            </div>`;

          if (!options[options.mode].hideDirectionsText) {
            suggestionsContainerScaffoldingMarkup += `
            <div class="search-autocomplete-container__directions">
              <span class="search-autocomplete-container__directions-item">Press <code>ENTER</code> to search for your current term or <code>ESC</code> to close.</span>
              <span class="search-autocomplete-container__directions-item">Press ↑ and ↓ to highlight a suggestion then <code>ENTER</code> to be redirected to that suggestion.</span>
            </div>`;
          }

          suggestionsContainerScaffoldingMarkup +=  '</div>';

          // Cache selectors.
          var $input = $(this);
          var $form = $('#federated-search-page-block-form');
          // Set up input with attributes, suggestions scaffolding.
          $input.attr("role","combobox")
              .attr("aria-owns","res")
              .attr("aria-autocomplete","list")
              .attr("aria-expanded","false");
          $(suggestionsContainerScaffoldingMarkup).insertAfter($input);
          // Cache inserted selectors.
          var $results = $('#res');
          var $autocompleteContainer = $('.js-search-autocomplete-container');
          var $closeButton = $('.js-search-autocomplete-container__close-button');

          // Initiate helper vars.
          var current;
          var counter = 1;
          var keys = {
            ESC: 27,
            TAB: 9,
            RETURN: 13,
            UP: 38,
            DOWN: 40
          };

          // Determine param values for any set default filters/facets.
          var defaultParams = '';
          $('input[type="hidden"]', $form).each(function(index, input) {
            var fq = $(input).attr('name') + ':("' + $(input).val() + '")';
            defaultParams += '&fq=' + encodeURI(fq);
          });
          var urlWithDefaultParams = options.url + defaultParams;


          // Bind events to input.
          $input.on("input", function(event) {
            doSearch(options.suggestionRows);
          });

          $input.on("keydown", function(event) {
            doKeypress(keys, event);
          });

          // Define event handlers.
          function doSearch(suggestionRows) {
            $input.removeAttr("aria-activedescendant");
            var value = $input.val();
            // Remove spaces on either end of the value.
            var trimmed = value.trim();
            // Default to the trimmed value.
            var query = trimmed;
            // If the current value has more than the configured number of characters.
            if (query.length > options.numChars) {
              // Append wildcard to the query if configured to do so.
              if (options.appendWildcard) {
                // Note: syntax for wildcard query depends on the query endpoint
                if (options.proxyIsDisabled) {
                  // One method of supporting search-as-you-type is to append a wildcard '*'
                  //   to match zero or more additional characters at the end of the users search term.
                  // @see: https://lucene.apache.org/solr/guide/6_6/the-standard-query-parser.html#TheStandardQueryParser-WildcardSearches
                  // @see: https://opensourceconnections.com/blog/2013/06/07/search-as-you-type-with-solr/
                  // Split into word chunks.
                  const words = trimmed.split(" ");
                  // If there are multiple chunks, join them with "+", repeat the last word + append "*".
                  if (words.length > 1) {
                    query = words.join("+") + words.pop() + '*';
                  } else {
                    // If there is only 1 word, repeat it an append "*".
                    query = words + '+' + words + '*';
                  }
                }
                else {
                  query = trimmed + '*';
                }
              }

              // Replace the placeholder with the query value.
              var pattern = new RegExp(/(\[val\])/, "gi");
              var url = urlWithDefaultParams.replace(pattern, query);

              // Set up basic auth if we need  it.
              var xhrFields = {};
              var headers = {};
              if (options.userpass) {
                xhrFields = {
                  withCredentials: true
                };
                headers = {
                  'Authorization': 'Basic ' + options.userpass
                };
              }

              // Make the ajax request
              $.ajax({
                xhrFields: xhrFields,
                headers: headers,
                url: url,
                dataType: 'json',
              })
                  // Currently we only support the response structure from Solr:
                  // {
                  //    response: {
                  //      docs: [
                  //        {
                  //        ss_federated_title: <result title as link text>,
                  //        ss_url: <result url as link href>,
                  //        }
                  //      ]
                  //    }
                  // }

                  // @todo provide hook for transform function to be passed in
                  //   via Drupal.settings then all it here.
                .done(function( results ) {
                  if (results.response.docs.length >= 1) {
                    // Remove all suggestions
                    $('.js-autocomplete-suggestion').remove();
                    $autocompleteContainer.removeClass('visually-hidden');
                    $("#search-autocomplete").append('');
                    $input.attr("aria-expanded", "true");
                    counter = 1;

                    // Bind click event for close button
                    $closeButton.on("click", function (event) {
                      event.preventDefault();
                      event.stopPropagation();
                      $input.removeAttr("aria-activedescendant");
                      // Remove all suggestions
                      $('.js-autocomplete-suggestion').remove();
                      $autocompleteContainer.addClass('visually-hidden');
                      $input.attr("aria-expanded", "false");
                      $input.focus();

                      // Emit a custom events for removing.
                      $(document).trigger("SearchApiFederatedSolr::block::autocomplete::suggestionsRemoved", [{
                      }]);
                    });

                    // Get first [suggestionRows] results
                    var limitedResults = results.response.docs.slice(0, suggestionRows);
                    limitedResults.forEach(function(item) {
                      // Highlight query chars in returned title
                      var pattern = new RegExp(trimmed, "gi");
                      var highlighted = item.ss_federated_title.replace(pattern, function(string) {
                        return "<strong>" + string + "</strong>"
                      });

                      // Default the URL to the passed ss_url.
                      let href = item.ss_url;

                      // Ensure that the result returned for the item from solr
                      // (via proxy or directly) is assigned an absolute URL.
                      if (!options.directUrl) {
                        // Initialize url to compute from solr sm_urls array.
                        let sm_url;
                        // Use the canonical url.
                        if (Array.isArray(item.sm_urls)) {
                          sm_url = item.sm_urls[0];
                        }

                        // If no valid urls are passed from solr, skip this item.
                        if (!sm_url) {
                          return;
                        }

                        // Override the current href value.
                        href = sm_url;
                      }

                      //Add results to the list
                      var $suggestionTemplate = `
                      <div role='option' tabindex='-1' class='js-autocomplete-suggestion autocomplete-suggestion' id='suggestion-${counter}'>
                        <a class='js-autocomplete-suggestion__link autocomplete-suggestion__link' href='${href}'>${highlighted}</a>
                        <span class='visually-hidden'>(${counter} of ${limitedResults.length})</span>
                      </div>`;
                      $results.append($suggestionTemplate);
                      counter = counter + 1;
                    });

                    // On link click, emit an event whose data can be used to write to analytics, etc.
                    $('.js-autocomplete-suggestion__link').on('click', function (e) {
                      $(document).trigger("SearchApiFederatedSolr::block::autocomplete::selection", [{
                        referrer: $(location).attr('href'),
                        target: $(this).attr('href'),
                        term: $input.val()
                      }]);
                    });

                    // Emit a custom events for results.
                    $(document).trigger("SearchApiFederatedSolr::block::autocomplete::suggestionsLoaded", [{
                    }]);

                    // Announce the number of suggestions.
                    var number = $results.children('[role="option"]').length;
                    if (number >= 1) {
                      Drupal.announce(Drupal.t(number + " suggestions displayed. To navigate use up and down arrow keys."));
                    }
                  } else {
                    // No results, remove suggestions and hide container
                    $('.js-autocomplete-suggestion').remove();
                    $autocompleteContainer.addClass('visually-hidden');
                    $input.attr("aria-expanded","false");

                    // Emit a custom events for removing.
                    $(document).trigger("SearchApiFederatedSolr::block::autocomplete::suggestionsRemoved", [{
                    }]);
                  }
                });
            }
            else {
              // Remove suggestions and hide container
              $('.js-autocomplete-suggestion').remove();
              $autocompleteContainer.addClass('visually-hidden');
              $input.attr("aria-expanded","false");

              // Emit a custom events for removing.
              $(document).trigger("SearchApiFederatedSolr::block::autocomplete::suggestionsRemoved", [{
              }]);
            }
          }

          function doKeypress(keys, event) {
            var $suggestions = $('.js-autocomplete-suggestion');
            var highlighted = false;
            highlighted = $results.children('div').hasClass('highlight');

            switch (event.which) {
              case keys.ESC:
                event.preventDefault();
                event.stopPropagation();
                $input.removeAttr("aria-activedescendant");
                $suggestions.remove();
                $autocompleteContainer.addClass('visually-hidden');
                $input.attr("aria-expanded","false");
                break;

              case keys.TAB:
                $input.removeAttr("aria-activedescendant");
                $suggestions.remove();
                $autocompleteContainer.addClass('visually-hidden');
                $input.attr("aria-expanded","false");
                break;

              case keys.RETURN:
                if (highlighted) {
                  event.preventDefault();
                  event.stopPropagation();
                  return selectOption(highlighted, $('.highlight').find('a').attr('href'));
                }
                else {
                  $form.submit();
                  return false;
                }
                break;

              case keys.UP:
                event.preventDefault();
                event.stopPropagation();
                return moveUp(highlighted);
                break;

              case keys.DOWN:
                event.preventDefault();
                event.stopPropagation();
                return moveDown(highlighted);
                break;

              default:
                return;
            }
          }

          function moveUp(highlighted) {
            $input.removeAttr("aria-activedescendant");

            // if highlighted exists and if the highlighted item is not the first option
            if (highlighted && !$results.children().first('div').hasClass('highlight')) {
              removeCurrent();
              current.prev('div').addClass('highlight').attr('aria-selected', true);
              $input.attr("aria-activedescendant", current.prev('div').attr('id'));
            }
            else {
              // Go to bottom of list
              removeCurrent();
              current = $results.children().last('div');
              current.addClass('highlight').attr('aria-selected', true);
              $input.attr("aria-activedescendant", current.attr('id'));
            }
          }

          function moveDown(highlighted) {
            $input.removeAttr("aria-activedescendant");

            // if highlighted exists and if the highlighted item is not the last option
            if (highlighted && !$results.children().last('div').hasClass('highlight')) {
              removeCurrent();
              current.next('div').addClass('highlight').attr('aria-selected', true);
              $input.attr("aria-activedescendant", current.next('div').attr('id'));
            }
            else {
              // Go to top of list
              removeCurrent();
              current = $results.children().first('div');
              current.addClass('highlight').attr('aria-selected', true);
              $input.attr("aria-activedescendant", current.attr('id'));
            }
          }

          function removeCurrent() {
            current = $results.find('.highlight');
            current.attr('aria-selected', false);
            current.removeClass('highlight');
          }

          function selectOption(highlighted, href) {
            if (highlighted && href) { // @todo add logic for non-link suggestions
              // Emit an event whose data can be used to write to analytics, etc.
              $(document).trigger("SearchApiFederatedSolr::block::autocomplete::selection", [{
                referrer: $(location).attr('href'),
                target: href,
                term: $input.val()
              }]);
              // Redirect to the selected link.
              $(location).attr("href", href);
            }
            else {
              return;
            }
          }
        });
    }
  };

  Drupal.SearchApiFederatedSolrAutocomplete = autocomplete;

})(jQuery, Drupal, drupalSettings);
