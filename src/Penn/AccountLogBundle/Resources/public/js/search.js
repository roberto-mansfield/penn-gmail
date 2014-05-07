$(function() {
	$("input#search_term").on("change", loadSearchResults);	
	$("button#search").on("click", loadSearchResults);
});

function loadSearchResults() {
	var search_term = $("input#search_term").val();
	if ( search_term ) {
		var url = $("button#search").attr("data-search-url") + '/' + search_term; 
		window.location.href = url;
	}
}