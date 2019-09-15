(function($) {
	
	$(document).ready(function(e) {
		
		$('#form_company_name').focus(function(e) {
			$(this).removeClass('invalid_field');
		});
		
		$('#form_email_address').focus(function(e) {
			$(this).removeClass('invalid_field');
		});
		
		$('#form_city').focus(function(e) {
			$(this).removeClass('invalid_field');
		});
		
		$('#form_province').focus(function(e) {
			$(this).removeClass('invalid_field');
		});
			
		var btnPressed = false;
		
		$('#send_data_form_btn').on('click', function(e) {
										
			e.preventDefault();
			
			if(!btnPressed) {
				
				btnPressed = true;
				
				//var $this = $(this);
			
				$('#send_data_form_response').html(wordpressOptionsObject.fieldValidation);
				
				// Collect data from inputs
				var reg_nonce = $('#pm_send_data_form_nonce').val();
				var reg_company_name = $('#form_company_name').val();
				var reg_email_address =  $('#form_email_address').val();
				var reg_city =  $('#form_city').val();
				var reg_province = $('#form_province').val();
				var reg_campaign_name = $('#form_campaign_name').val();
				var reg_campaign_groups = JSON.parse($('#form_campaign_groups').val());
				var reg_campaign_message = $('#form_campaign_message').val();
				var reg_recaptcha_response = grecaptcha.getResponse();
		
				
				/**
				 * AJAX URL where to send data 
				 * (from localize_script)
				 */
				var ajax_url = pm_ln_register_vars.pm_ln_ajax_url;
			
				// Data to send
				var data = {
				  action: 'send_data_form', //send_appointment_form
				  nonce: reg_nonce,
				  company_name: reg_company_name,
				  email_address: reg_email_address,
				  city: reg_city,
				  province: reg_province,
				  campaign_name: reg_campaign_name,
				  campaign_groups: reg_campaign_groups,
				  campaign_message: reg_campaign_message,
				  recaptcha_response : reg_recaptcha_response
				};	
						
				
				// Do AJAX request
				$.post( ajax_url, data, function(response) {
			
				  // If we have response
				  if(response) {
					  
					console.log(response);
												
					if(response === "company_name_error") {
						
						$('#send_data_form_response').html(wordpressOptionsObject.dataFormError1);
						$('#form_company_name').addClass('invalid_field');
						
						btnPressed = false;
						
					} else if(response === "email_error") {
						
						$('#send_data_form_response').html(wordpressOptionsObject.dataFormError2);
						$('#form_email_address').addClass('invalid_field');
						
						btnPressed = false;
						
					} else if(response === "city_error") {
						
						$('#send_data_form_response').html(wordpressOptionsObject.dataFormError3);
						$('#form_city').addClass('invalid_field');
						
						btnPressed = false;
						
					} else if(response === "province_error") {
						
						$('#send_data_form_response').html(wordpressOptionsObject.dataFormError4);
						$('#form_province').addClass('invalid_field');
						
						btnPressed = false;
						
					} else if(response === "recaptcha_error") {
						
						$('#send_data_form_response').html(wordpressOptionsObject.dataFormError5);
						
						btnPressed = false;
						
					} else if(response === "success"){
						
						$('#send_data_form_response').html(wordpressOptionsObject.successMessage);
						$('#send_data_form_btn').fadeOut();
						
					} else if(response === "failed"){
						
						$('#send_data_form_response').html(wordpressOptionsObject.failedMessage);
						$('#send_data_form_btn').fadeOut();
						
					}
					
				  }
				});
				
			}
									
			
			
			
		});//end of click function
		
	});
	
})(jQuery);