/**
 * @file
 * accordion javascript for search filters
 */
(function(document,window,$) {
  $(document).ready(function () {

    var windowWidth = $(window).width();
    if (windowWidth <= 900) {

      $('.search-filters .search-filters__trigger').click(function (e) {
        $('.search-filters .search-filters__trigger').removeClass('js-search-filters-open');
        $('.search-filters .search-filters__form').slideUp();
        if ($(this).next().is(':hidden') == true) {
          $(this).addClass('js-search-filters-open');
          $(this).next().slideDown();
        }
        e.preventDefault();
      });

      $('.search-filters .search-filters__form').hide();
    }
  });
})(document,window,jQuery);
