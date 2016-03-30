function parseAjaxResponse(res){
	var st = res.indexOf("[AJAX_RESPONSE]") + 15;
	var en = res.indexOf("[/AJAX_RESPONSE]");
	var j = res.substring(st, en);
	return jQuery.parseJSON( j );
}

/**
 * type string success|info|warning|danger
 */
function addFlashMsg(type, msg, clear){
	if (clear)
		$('#flash_messages').html('<div class="alert alert-'+type+'">'+msg+'</div>');
	else
		$('#flash_messages').append('<div class="alert alert-'+type+'">'+msg+'</div>');
}

function clearFlashMsgs(type, msg){
	$('#flash_messages').html('');
}

function updateProgressBar($barContainer, percent){
	if (percent > 100)
		percent = 100;
	$barContainer.show().find('.progress-bar').css('width', percent+'%');
	$barContainer.find('span').html(percent + '%');
}

function ucfirst(str){
	str += '';
	var f = str.charAt(0).toUpperCase();
	return f + str.substr(1);
}

$(document).ready(function(){
	$('.checkAll').change(function(){
		var checked = $(this).is(':checked');
		$(this).closest('table').find('input[type=checkbox]').prop('checked', checked);
	});
});