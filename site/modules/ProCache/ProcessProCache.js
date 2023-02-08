jQuery(document).ready(function($) {
	
	if(!$('#ProcessProCache').length) return;
	
	$('#ProcessProCache').WireTabs({
		items: $('.Inputfields li.WireTab')
	});
	
	$('.notes').find('br').each(function() {
		if($(this).closest('#wrap_Inputfield__clearMin').length) return;
		var $hr = $("<div />").css('margin', '9px 0 9px 0');
		$(this).after($hr);
	});
	
	$('#wrap_Inputfield_cacheTemplates').find('em').each(function() {
		$(this).css('font-style', 'normal').addClass('detail');	
	});
	
	$('input.cacheTemplates').on('input', function() {
		var $tr = $(this).closest('tr');
		if($(this).is(':checked')) {
			$tr.removeClass('pwpc-off'); 
		} else {
			$tr.addClass('pwpc-off'); 	
		}
	}); 
	
});
