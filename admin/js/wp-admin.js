// JavaScript Document

(function($) {
	
	$(document).ready(function(e) {
		        
		//Purchase btn image preview
		if( $('.pm-purchase-image-upload-field').length > 0 ){
	
			var value = $('.pm-purchase-image-upload-field').val();
			
			if (value !== '') {
				
				$('.pm-paypal-purchase-btn-img-preview').html('<img src="'+ value +'" />');
				
			}
	
		}//end if
		
		//Remove banner image btn
		if( $('#remove_banner_image_button').length > 0 ){
			
			$('#remove_banner_image_button').on('click', function(e) {
				
				$('#newsletter_banner_img_field').val('');
				$('#pm_banner_image_preview').empty();
				
			});
			
		}//end if
		
		
		//Add new call to action entry
		//console.log(wordpressAdminOptionsObject.urls);
		
		if( $('#pm-add-new-call-to-action-btn').length > 0 ){
		
			$('#pm-add-new-call-to-action-btn').on('click', function(e) {
				
				e.preventDefault();
				
				var counterValueIdFinal = 0;
				
				//Get counter value based on last input field in container
				if( $('#pm-call-to-actions-container').find('.pm-call-to-actions-field-container:last-child').length > 0 ){
					
					var counterValue = $('.pm-call-to-actions-field-container:last-child').attr('id'),
					counterValueId = counterValue.substring(counterValue.lastIndexOf('_') + 1);
					
					counterValueIdFinal = ++counterValueId;
					
				} else {
					
					$('#pm-call-to-actions-container').html('');
					
				}
				
				//Append new call to action field
				var wrapperStart = '<div class="pm-call-to-actions-field-container" id="pm_call_to_actions_field_container_'+ counterValueIdFinal +'">';				
								
				var field1 = '<textarea name="pm_call_to_action_post[]" class="form-field textarea" id="pm_call_to_action_post_'+ counterValueIdFinal +'" placeholder="Message"></textarea>';	
				
				var field2 = '<select name="pm_call_to_action_post_url[]" id="pm_call_to_action_post_url_'+ counterValueIdFinal +'">';	
					
				var field3 = '<option value="default">-- Button URL --</option>';
				
				var ctaList = $("#pm_call_to_action_post_url_list");
				
				ctaList.children().each(function(index, element) {
                    					
					field3 += '<option value="'+ $(element).data("url") +'">'+ $(element).data("title") +'</option>';
					
                });
				
								
				var field4 = '</select>';	
				
				var field5 = '<br><input type="button" value="Remove Entry" class="button button-secondary button-large delete pm-remove-call-to-action-entry-btn" id="pm_slider_system_post_remove_btn_'+counterValueIdFinal+'" />';
				
				var wrapperEnd = '</div>';
				
				$('#pm-call-to-actions-container').append(wrapperStart + field1 + field2 + field3 + field4 + field5 + wrapperEnd);	
					
				
			});
			
		}
		
		$( 'body' ).on( 'click', '.pm-remove-call-to-action-entry-btn', function(e){
				
			e.preventDefault();
					
			//get the id for the target container
			var btnId = $(this).attr('id'),
			targetContainerID = btnId.substring(btnId.lastIndexOf('_') + 1);
			
			//remove the entry
			$('#pm_call_to_actions_field_container_'+targetContainerID).remove();
			
		});
		
		$( 'body' ).on( 'click', '.delete-user-group-btn', function(e){
				
			e.preventDefault();
					
			var entryId = $(this).data("id");
			
			var c = confirm("Warning! Deleting this group will also delete all assigned emails from the database. Continue?");
			
			if (c == true) {
				$("#delete_user_group").val(entryId);
				$("#delete_user_group_form").submit();
			} else {
				//do nothing
			} 			
			
		});
		
		
		$( 'body' ).on( 'click', '.delete-mp-email-btn', function(e){
				
			e.preventDefault();
					
			var entryId = $(this).data("id");
			//alert(entryId);
			
			$("#delete_mp_email").val(entryId);
			
			$("#delete_mp_email_form").submit();
			
		});
		
		$( 'body' ).on( 'click', '.delete-collected-data-entry-btn', function(e){
				
			e.preventDefault();
					
			var entryId = $(this).data("id");
			//alert(entryId);
			
			$("#delete_collected_data_entry").val(entryId);
			
			$("#collected_data_form").submit();
			
		});
		
		$( 'body' ).on( 'click', '#purge_all_collected_data_btn', function(e){
				
			e.preventDefault();
			
			var c = confirm("Warning! This will delete all data entries. Continue?");
			
			if (c == true) {
				$("#delete_all_collected_data_form").submit();
			} else {
				//do nothing
			} 
			
		});
		
		//Documentation page
		$( 'body' ).on( 'click', '.documentation-button', function(e){
				
			e.preventDefault();
			
			var $this = $(this),
			section = $this.attr('id'),
			targetContent = $('#' + section + '_content');
			
			
			if(!$this.hasClass('active')) {
				
				//display content
				$this.addClass('active');
				targetContent.show(250);
				
			} else {
				
				//hide content
				$this.removeClass('active');
				targetContent.hide(250);
					
			}
			
			
		});
		
		
    }); //end of document ready
	
})(jQuery);