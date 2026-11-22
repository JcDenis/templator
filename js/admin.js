$(function(){
	/* toogle admin form sidebar */
	const title = document.querySelector('#templator h5');
	if (title) {
		dotclear.toggleWithLegend(title, document.querySelectorAll('#templator > :not(h5)'), {
			user_pref: 'dcx_templator_admin_form_sidebar',
			legend_click: true,
		});
	}
});