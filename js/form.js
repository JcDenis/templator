$(function() {
	var t = $('#templator-control');
	t.css('display','inline');
	$('#add-template').hide();
	t.click(function() {
		$('#add-template').show();
		$(this).hide();
		return false;
	});



		
});
