(function($){
	
	'use strict'

	$(document).ready(function(e) {
		
		//alert('pm-image-uploader loaded');
		
		if(wp.media !== undefined){
				
			var banner_image_uploader;
			
			
			//Post image uploader
			$('#banner_upload_image_button').on('click', function(e) {
												
				 e.preventDefault();
	
				 //If the uploader object has already been created, reopen the dialog
				 if (banner_image_uploader) {
					 banner_image_uploader.open();
					 return;
				 }
				 
				 //Extend the wp.media object
				 banner_image_uploader = wp.media.frames.file_frame = wp.media({
					title: 'Choose Image',
					button: {
						text: 'Choose Image'
					},
					 multiple: false
				 });	
				 
				 //When a file is selected, grab the URL and set it as the text field's value
				 banner_image_uploader.on('select', function() {
					 					 
					var attachment = banner_image_uploader.state().get('selection').first().toJSON();
					var url = '';
					url = attachment['url'];
										
					$('#newsletter_banner_img_field').val(url);
					$('#pm_banner_image_preview').html('<img src="'+ url +'" />');
		
				 });
				 
				 //Finally, open the modal on click
				 banner_image_uploader.open();
				
			});			
			
		}
		
	});

})(jQuery);