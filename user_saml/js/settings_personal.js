$(document).ready(function(){
	$('#passwordform').remove();
	$('#displaynameform').remove();
	$('#lostpassword').remove();
	$('#cpassworderror').hide();
	$('#cpasswordchanged').hide();
	$('#user_name_login').css({"width":"17em", "background-color":"rgb(220, 220, 220)"});
	$('#user_name_login').attr("readonly", true);
	$('#clientsettings fieldset').css({ "padding-top":"0px", "padding-bottom":"10px", "border-top": "0px solid"});
	$('#clientsettings br').css({"line-height":"0px", "display":"block"});
	var clientbox = $('#clientsettings').detach();
	$('#quota').before(clientbox);
	
	$('#client_setpwd').click( function(){
		if ($('#client_pwd1').val() !== '' && $('#client_pwd2').val() !== '') {
		// Serialize the data
			$('#cpasswordchanged').hide();
			$('#cpassworderror').hide();
			if ($('#client_pwd1').val() !== $('#client_pwd2').val()) {
				$('#cpassworderror').text(t("user_saml","Passwords doesn't match"));
				$('#cpassworderror').show();
				$('#cpassworderror').fadeOut(9000);
				return false;
			}
			var post = $('#clientpasswordform').serialize();
			$.ajax({
				type: "POST",
				data: post,
				cache: false,
				dataType: "json",
				success: function(data) {
					if( data.status === "success" ){
						$('#client_pwd1').val('');
						$('#client_pwd2').val('');
						$('#cpasswordchanged').show();
						$('#cpasswordchanged').fadeOut(9000);
					} else {
						if (typeof(data.data) !== "undefined") {
							$('#cpassworderror').text(data.data.message);
						} else {
							$('#cpassworderror').text(t('user_saml', 'Unable to set password'));
						}
						$('#cpassworderror').show();
						$('#cpassworderror').fadeOut(9000);
					}
				},
				error: function() { alert('Error changing password.'); }
			});
			return false;
		} else {
			return false
		}
	});
});
