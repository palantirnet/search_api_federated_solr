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
          var suggestionsContainerScaffoldingMarkup = '<div class="search-autocomplete-container visually-hidden"><div class="search-autocomplete-container__title">' + options[options.mode].titleText  + '<button class="search-autocomplete-container__close-button">x</button></div><div id="search-autocomplete"><div id="res" role="listbox" tabindex="-1"></div></div>';

          if (!options[options.mode].hideDirectionsText) {
            suggestionsContainerScaffoldingMarkup += '<div class="search-autocomplete-container__directions"><span class="search-autocomplete-container__directions-item">Press <code>ENTER</code> to search for your current term or <code>ESC</code> to close.</span><span class="search-autocomplete-container__directions-item">Press ↑ and ↓ to highlight a suggestion then <code>ENTER</code> to be redirected to that suggestion.</span></div>';
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
          var $autocompleteContainer = $('.search-autocomplete-container');
          var $closeButton = $('.search-autocomplete-container__close-button');

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
            defaultParams += '&' + $(input).attr('name') + '=' + encodeURI($(input).val());
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
                    $('.autocomplete-suggestion').remove();
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
                      $('.autocomplete-suggestion').remove();
                      $autocompleteContainer.addClass('visually-hidden');
                      $input.attr("aria-expanded", "false");
                      $input.focus();
                    });

                    // Get first [suggestionRows] results
                    var limitedResults = results.response.docs.slice(0, suggestionRows);
                    limitedResults.forEach(function(item) {
                        // Highlight query chars in returned title
                        var pattern = new RegExp(trimmed, "gi");
                        var highlighted = item.ss_federated_title.replace(pattern, function(string) {
                          return "<strong>" + string + "</strong>"
                        });

                        //Add results to the list
                        $results.append("<div role='option' tabindex='-1' class='autocomplete-suggestion' id='suggestion-" + counter + "'><a class='autocomplete-suggestion__link' href='" + item.ss_url + "'>" + highlighted + "</a><span class='visually-hidden'>(" + counter + " of " + limitedResults.length + ")</span></div>");
                        counter = counter + 1;
                    });

                    // On link click, emit an event whose data can be used to write to analytics, etc.
                    $('.autocomplete-suggestion__link').on('click', function (e) {
                      $(document).trigger("SearchApiFederatedSolr::block::autocomplete::selection", [{
                        referrer: $(location).attr('href'),
                        target: $(this).attr('href'),
                        term: $input.val()
                      }]);
                    });

                    // Announce the number of suggestions.
                    var number = $results.children('[role="option"]').length;
                    if (number >= 1) {
                      Drupal.announce(Drupal.t(number + " suggestions displayed. To navigate use up and down arrow keys."));
                    }
                  } else {
                    // No results, remove suggestions and hide container
                    $('.autocomplete-suggestion').remove();
                    $autocompleteContainer.addClass('visually-hidden');
                    $input.attr("aria-expanded","false");
                  }
                });
            }
            else {
              // Remove suggestions and hide container
              $('.autocomplete-suggestion').remove();
              $autocompleteContainer.addClass('visually-hidden');
              $input.attr("aria-expanded","false");
            }
          }

          function doKeypress(keys, event) {
            var $suggestions = $('.autocomplete-suggestion');
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
