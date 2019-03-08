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
        .find('#edit-search[data-search-api-autocomplete-search]')
        .once('search-api-federated-solr-autocomplete-search')
        .each(function () {
          var $input = $(this);
          var $form = $('#federated-search-page-block-form');

          $input.attr("role","combobox")
              .attr("aria-owns","res")
              .attr("aria-autocomplete","list")
              .attr("aria-expanded","false");

          $('<div class="search-autocomplete-container visually-hidden"><div class="search-autocomplete-container__title">What are you looking for?<button class="search-autocomplete-container__close-button">x</button></div><div id="search-autocomplete"><div id="res" role="listbox" tabindex="-1"></div></div><div class="search-autocomplete-container__directions"><span class="search-autocomplete-container__directions-item">Press <code>ENTER</code> to search for your current term or <code>ESC</code> to close.</span><span class="search-autocomplete-container__directions-item">Press ↑ and ↓ to highlight a suggestion then <code>ENTER</code> to be redirected to that suggestion.</span></div></div>').insertAfter($(this));

          var $results = $('#res');
          var $autocompleteContainer = $('.search-autocomplete-container');
          var $closeButton = $('.search-autocomplete-container__close-button');

          var resultsLimit = 5;
          var current;
          var counter = 1;
          var keys = {
            ESC: 27,
            TAB: 9,
            RETURN: 13,
            UP: 38,
            DOWN: 40
          };

          $(this).on("input", function(event) {
            doSearch(resultsLimit);
          });

          $(this).on("keydown", function(event) {
            doKeypress(keys, event);
          });

          function doSearch(resultsLimit) {
            $input.removeAttr("aria-activedescendant");
            var query = $input.val();
            if (query.length >= 2) {

              $.ajax({
                // @todo: get this from config
                url: "/search_api_autocomplete/quick_search?display=page_1&filter=full_text_title&q=" + query
              })
                .done(function( results ) {
                  if (results.length >= 1) {
                    // Remove all suggestions
                    $('.autocomplete-suggestion').remove();
                    $autocompleteContainer.removeClass('visually-hidden');
                    $("#search-autocomplete").append('');
                    $input.attr("aria-expanded","true");
                    counter = 1;
                  }

                  // Bind click event for close button
                  $closeButton.on("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    $input.removeAttr("aria-activedescendant");
                    // Remove all suggestions
                    $('.autocomplete-suggestion').remove();
                    $autocompleteContainer.addClass('visually-hidden');
                    $input.attr("aria-expanded","false");
                    $input.focus();
                  });

                  //Add results to the list
                  for (var term in results) {
                    if (counter <= resultsLimit) {
                      $results.append("<div role='option' tabindex='-1' class='autocomplete-suggestion' id='suggestion-" + counter + "'><a class='autocomplete-suggestion__link' href='" + results[term].value + "'>" + results[term].label.replace(/(<([^>]+)>)/ig,"").trim() + "</a></div>");
                      counter = counter + 1;
                    }
                  }
                  var number = $results.children('[role="option"]').length;
                  if (number >= 1) {
                    Drupal.announce(Drupal.t(number + " suggestions displayed. To navigate use up and down arrow keys."));
                  }
                });
            }
            else {
              // Remove all suggestions
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
