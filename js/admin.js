$(function(){
	/* toogle admin form sidebar */
	$('#templator h5').toggleWithLegend(
		$('#templator').children().not('h5'),
		{cookie:'dcx_templator_admin_form_sidebar',legend_click:true}
	);
});