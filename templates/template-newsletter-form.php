<?php 

/* 
Template Name: Newsletter Template (Form)
Template Post Type: otanewsletters
*/ 

?>

<?php 

	$newsletter_banner_img = get_post_meta( $post->ID, 'newsletter_banner_img', true );
	$pm_show_translation_btn = get_post_meta( $post->ID, 'pm_show_translation_btn', true );
	$pm_show_logos = get_post_meta( $post->ID, 'pm_show_logos', true );
	
	$pm_return_to_page_url_meta = get_post_meta( $post->ID, 'pm_return_to_page_url_meta', true );
	$pm_form_message_meta = get_post_meta( $post->ID, 'pm_form_message_meta', true );
	
	$pm_form_email_body_message_meta = get_post_meta( $post->ID, 'pm_form_email_body_message_meta', true );
	
	$google_recaptcha_site_key = get_option('google_recaptcha_site_key');

?>

<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>


<body class="page-letter <?php echo isset(ICL_LANGUAGE_CODE) ? ICL_LANGUAGE_CODE : '' ?>">

<div id="pm_layout_wrapper">
    
    <!-- Header area -->
    <?php if( !empty($newsletter_banner_img) ) { ?>
    	<section class="hero" style="background-image:url(<?php echo esc_url($newsletter_banner_img); ?>)">
    <?php } else { ?>
    	<section class="hero">
    <?php } ?>
                    
        <div class="pm-banner-overlay"></div>
        <div class="pm-pattern-overlay" id="pm-pattern-overlay"></div>
                
    </section>
    <!-- /Header area end -->
                    
    <!-- BODY CONTENT starts here -->
    
    <!-- PANEL 1 -->
    <article class="letter" id="article">
    
        <div class="container">
        
            <div class="row">
            
                <div class="col-lg-6 col-md-6 col-sm-6">
                
                	<?php if(!empty($pm_return_to_page_url_meta)) : ?>
                    
                    	<?php if( ICL_LANGUAGE_CODE ) { ?>
                        
                        	<?php 
							
								switch(ICL_LANGUAGE_CODE) {
									
									case "en" :									
										echo '<a href="'. esc_url($pm_return_to_page_url_meta) .'" class="pm-return-btn"><i class="fa fa-angle-left"></i> Return to previous page</a>';									
									break;
									
									case "fr";									
										echo '<a href="'. esc_url($pm_return_to_page_url_meta) .'" class="pm-return-btn"><i class="fa fa-angle-left"></i> Retourner à la page précédente</a>';									
									break;
									
								}
							
							?>                        	
                        
                        <?php } else { ?>
                        
                        	<a href="<?php echo esc_url($pm_return_to_page_url_meta); ?>" class="pm-return-btn"><i class="fa fa-angle-left"></i> <?php _e('Return to previous page', 'newslettermanagement'); ?></a>
                        
                        <?php } ?>
                    
                    <?php endif; ?>
                    
                </div>
                
                <?php if( $pm_show_translation_btn === "yes" ) : ?>
    
					<?php if( function_exists('icl_get_languages') ) : ?>
                    
                        <?php $languages = icl_get_languages('skip_missing=1'); ?>
                        
                        <?php if(1 < count($languages)) { ?>
                        
                            <?php  
                            
                                foreach($languages as $l){
                                    
                                    if(ICL_LANGUAGE_CODE == "fr" && $l['language_code'] == "en") {
                                        
                                        echo '<a href="'.$l['url'].'" class="pm-language-btn relative">English</a>';
                                        
                                    } 
                                    
                                    if(ICL_LANGUAGE_CODE == "en" && $l['language_code'] == "fr") {
                                        
                                        echo '<a href="'.$l['url'].'" class="pm-language-btn relative">Francais?</a>';
                                        
                                    } 
                                    
                                    //if(!$l['active']) echo '<li><img src="'.$l['country_flag_url'].'" alt="'.$l['translated_name'].'" /><a href="'.$l['url'].'">'.$l['translated_name'].'</a></li>';
                                    
                                } 
                                
                            ?>
                        
                        <?php } ?>
                    
                    <?php endif; ?>
                    
                <?php endif; ?>
                
            </div>
        
        </div>
    
        <div class="container body">
    
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 newsletter-body">                	
                    
                    <?php //echo do_shortcode($post->post_content); ?>
                    
                    <?php if (have_posts()) :  while (have_posts()) : the_post(); ?>
                
						<?php the_content(); ?>
                        
                    <?php endwhile; else: ?>
                    <?php endif; ?>

                </div><!-- /.col -->
            </div><!-- /.row -->
            
            <div class="pm-divider"></div>
            
            <div class="row">
            
                <div class="col-lg-12 col-md-12 col-sm-12 pm-containerPadding-bottom-30">
                    <p class="form-message"><b><?php echo esc_attr($pm_form_message_meta); ?></b></p>
                    
                    <?php 
						
						$term_list = wp_get_post_terms($post->ID, 'newslettercampaigns');
						//echo $term_list[0]->name;
						
						/*
						$terms = get_terms('newslettercampaigns');						
						foreach ($terms as $term) {
							echo ucfirst($term->name);	
						}
						*/
						
						//retrieve user groups
						$pm_user_groups_meta = get_post_meta( $post->ID, 'pm_user_groups_meta', true );
						$user_groups = json_encode($pm_user_groups_meta);
						//print_r($pm_user_groups_meta); 
						
						//TESTING
						/*$campaign_groups = json_decode($user_groups);
						$emails_array = array();						
						
						for( $i = 0; $i < count($campaign_groups); $i++ ) {
							
							$group_id = $campaign_groups[$i];
							//echo '$group_id = ' . $campaign_groups[$i];
							
							$group_results = $wpdb->get_results( "SELECT g.*, e.email_address FROM {$wpdb->prefix}newsletter_user_groups g INNER JOIN {$wpdb->prefix}newsletter_mp_emails e ON g.id = e.group_id WHERE g.id = {$group_id}", ARRAY_A );
							
							if( is_array($group_results) ) {
							
								foreach($group_results as $result) {
									
									//$recipient = $result['email_address'] . ' | ' . $result['group_name'] . '<br>';
									$recipient = $result['email_address'];
									//echo $recipient . '<br>';
									
									array_push($emails_array, $recipient);
									
								}
							
							}
							
						}//end of for
						
						$emails_final = implode(", ", $emails_array);
						
						//print_r($emails_final);
						
						//Send email to recipient
						$subj = esc_html__('ELD Title goes here', 'otanewsletters');		
			
						$body = ' 
						
						  **** '. esc_html__('THIS IS AN AUTOMATED MESSAGE. PLEASE DO NOT REPLY DIRECTLY TO THIS EMAIL', 'otanewsletters') .' **** 
						  
						  '. esc_html__('Company Name', 'otanewsletters') .': Test
						  '. esc_html__('Email Address', 'otanewsletters') .': Test
						  '. esc_html__('City', 'otanewsletters') .': Test
						  '. esc_html__('Province', 'otanewsletters') .': Test
						  
						';
						
						$send_mail = wp_mail( $emails_final, $subj, $body );
						
						if($send_mail) {
							echo 'Mail sent';
						} else {
							echo 'Mail failed';	
						}*/
						
					?>
                    
                </div>
                
                <form action="<?php echo get_permalink(); ?>" method="post">
                
                	<?php if( ICL_LANGUAGE_CODE ) { ?>
                        
						<?php 
                        
                            switch(ICL_LANGUAGE_CODE) {
                                
                                case "en" :		
								
									?>							
                                    
									<div class="col-lg-6 col-md-6 col-sm-6">                        
										<input type="text" placeholder="Company Name" name="company_name" id="form_company_name" class="form-control" required />                            
									</div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">                            
										<input type="email" placeholder="<?php esc_attr_e('Email Address', 'newslettermanagement'); ?>" name="email_address" id="form_email_address" class="form-control" required />                        			</div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">
										<input type="text" placeholder="<?php esc_attr_e('City', 'newslettermanagement'); ?>" class="form-control" name="city" id="form_city" required />
									</div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">
										<input type="text" placeholder="<?php esc_attr_e('Province', 'newslettermanagement'); ?>" class="form-control" name="province" id="form_province" required />
									</div>
                                    
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="recaptcha-container">
                                            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($google_recaptcha_site_key); ?>"></div>
                                        </div>
                                    </div>
									
									<div class="col-lg-12 col-md-12 col-sm-12">
										<a href="#" class="form-button send-data-form" id="send_data_form_btn"><?php esc_attr_e('Send', 'newslettermanagement'); ?></a>
									</div>
                                    
                                    <?php
																		
                                break;
                                
                                case "fr";									
                                    
									?>							
                                    
									<div class="col-lg-6 col-md-6 col-sm-6">                        
										<input type="text" placeholder="Nom de la compagnie" name="company_name" id="form_company_name" class="form-control" required />                            
									</div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">                            
										<input type="email" placeholder="Adresse e-mail" name="email_address" id="form_email_address" class="form-control" required />                        			
                                    </div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">
										<input type="text" placeholder="Ville" class="form-control" name="city" id="form_city" required />
									</div>
									
									<div class="col-lg-6 col-md-6 col-sm-6">
										<input type="text" placeholder="Province" class="form-control" name="province" id="form_province" required />
									</div>
                                    
                                    <div class="col-lg-12 col-md-12 col-sm-12">
                                        <div class="recaptcha-container">
                                            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($google_recaptcha_site_key); ?>"></div>
                                        </div>
                                    </div>
									
									<div class="col-lg-12 col-md-12 col-sm-12">
										<a href="#" class="form-button send-data-form" id="send_data_form_btn">Envoyer</a>
									</div>
                                    
                                    <?php
																		
                                break;
                                
                            }
                        
                        ?>                        	
                    
                    <?php } else {//default english form ?>
                    
                    	<div class="col-lg-6 col-md-6 col-sm-6">                        
                                <input type="text" placeholder="Company Name" name="company_name" id="form_company_name" class="form-control" required />                            
                        </div><!-- /.col -->
                        
                        <div class="col-lg-6 col-md-6 col-sm-6">                            
                                <input type="email" placeholder="<?php esc_attr_e('Email Address', 'newslettermanagement'); ?>" name="email_address" id="form_email_address" class="form-control" required />                        </div><!-- /.col -->
                        
                        <div class="col-lg-6 col-md-6 col-sm-6">
                                <input type="text" placeholder="<?php esc_attr_e('City', 'newslettermanagement'); ?>" class="form-control" name="city" id="form_city" required />
                        </div><!-- /.col -->
                        
                        <div class="col-lg-6 col-md-6 col-sm-6">
                                <input type="text" placeholder="<?php esc_attr_e('Province', 'newslettermanagement'); ?>" class="form-control" name="province" id="form_province" required />
                        </div><!-- /.col -->
                        
                        <div class="col-lg-12 col-md-12 col-sm-12">
                        	<div class="recaptcha-container">
                            	<div class="g-recaptcha" data-sitekey="<?php echo esc_attr($google_recaptcha_site_key); ?>"></div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12 col-md-12 col-sm-12">
                                <a href="#" class="form-button send-data-form" id="send_data_form_btn"><?php esc_attr_e('Send', 'newslettermanagement'); ?></a>
                        </div><!-- /.col -->
                    
                    <?php } ?>
                    
                    <input type="hidden" name="campaign_name" id="form_campaign_name" value="<?php echo $term_list[0]->name; ?>" />
                    <input type="hidden" name="campaign_groups" id="form_campaign_groups" value='<?php echo $user_groups; ?>' />
                    <input type="hidden" name="campaign_message" id="form_campaign_message" value="<?php echo $pm_form_email_body_message_meta; ?>" />
                                    
                	<?php wp_nonce_field('pm_ln_nonce_action', 'pm_send_data_form_nonce');  ?>
                
                </form>
                
            </div><!-- /.row -->
            
            <div id="send_data_form_response"></div>
            
            <div class="row">
            
                <div class="col-lg-12 col-md-12 col-sm-12 pm-align-center pm-containerPadding-top-60">
                
                    <a href="#" class="back-to-top" id="pm-back-top"><i class="fa fa-chevron-up"></i></a>
                
                </div>
            
            </div>
            
            <br><br>
            
            <?php if($pm_show_logos === "yes") : ?>
            
            	<div class="row">
            
					<?php
                    
                        $url = plugins_url();
                        $plugin_path = parse_url($url);
                        //var_dump($path['path']);
                    
                    ?>
                
                    <div class="col-lg-3 col-md-3 col-sm-3 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/1.jpg" alt="AMTA" />
                    </div>
                    
                    <div class="col-lg-3 col-md-3 col-sm-3 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/2.jpg" alt="APTA" />
                    </div>
                    
                    <div class="col-lg-3 col-md-3 col-sm-3 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/3.jpg" alt="BCTA" />
                    </div>
                    
                    <div class="col-lg-3 col-md-3 col-sm-3 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/4.jpg" alt="MTA" />
                    </div>
                    
                    <div class="col-lg-4 col-md-4 col-sm-4 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/5.jpg" alt="AMTA" />
                    </div>
                    
                    <div class="col-lg-4 col-md-4 col-sm-4 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/6.jpg" alt="APTA" />
                    </div>
                    
                    <div class="col-lg-4 col-md-4 col-sm-4 pm-align-center pm-logo-col">
                        <img src="<?php echo PM_FRONT_URL;  ?>/img/logos/7.jpg" alt="BCTA" />
                    </div>
                
                </div><!-- /.row -->
            
            <?php endif; ?>
            
        </div><!-- /.container -->
    </article>
    <!-- PANEL 1 end -->
    
    <!-- BODY CONTENT ends here -->


</div><!-- /pm_layout-wrapper -->

<?php wp_footer(); ?>
</body>

</html>