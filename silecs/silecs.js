$(function() {
    $('<a href="#" id="filter-toggler">☰</a>').prependTo(".filter-box").on("click", function() {
        $(".save-query, .manage-queries, .filter-links").toggle();
    });
});
