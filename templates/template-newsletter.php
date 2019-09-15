<?php 

/* 
Template Name: Newsletter Template (Landing Page)
Template Post Type: otanewsletters
*/ 

?>

<?php 

	$newsletter_banner_img = get_post_meta( $post->ID, 'newsletter_banner_img', true );
	$newsletter_banner_msg = get_post_meta( $post->ID, 'newsletter_banner_msg', true );
	$pm_show_translation_btn = get_post_meta( $post->ID, 'pm_show_translation_btn', true );
	$pm_call_to_actions = get_post_meta( $post->ID, 'pm_call_to_actions', true ); //ARRAY VALUE
	$pm_show_logos = get_post_meta( $post->ID, 'pm_show_logos', true );

?>

<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body>


<div id="pm_layout_wrapper">
    
    <?php if( $pm_show_translation_btn === "yes" ) : ?>
    
    	<?php if( function_exists('icl_get_languages') ) : ?>
        
        	<?php $languages = icl_get_languages('skip_missing=1'); ?>
            
            <?php if(1 < count($languages)) { ?>
            
            	<?php  
				
					foreach($languages as $l){
						
						if(ICL_LANGUAGE_CODE == "fr" && $l['language_code'] == "en") {
							
							echo '<a href="'.$l['url'].'" class="pm-language-btn scroll">English</a>';
							
						} 
						
						if(ICL_LANGUAGE_CODE == "en" && $l['language_code'] == "fr") {
							
							echo '<a href="'.$l['url'].'" class="pm-language-btn scroll">Francais?</a>';
							
						} 
						
						//if(!$l['active']) echo '<li><img src="'.$l['country_flag_url'].'" alt="'.$l['translated_name'].'" /><a href="'.$l['url'].'">'.$l['translated_name'].'</a></li>';
						
					} 
					
			    ?>
            
            <?php } ?>
        
        <?php endif; ?>
    	
    <?php endif; ?>

    <!-- Header area -->
    <?php if( !empty($newsletter_banner_img) ) { ?>
    	<section class="hero" style="background-image:url(<?php echo esc_url($newsletter_banner_img); ?>)">
    <?php } else { ?>
    	<section class="hero">
    <?php } ?>
    
            
        <h1><?php echo esc_attr($newsletter_banner_msg); ?></h1>
        
        <div class="pm-hero-banner-fade">
            <a href="#article" class="pm-hero-banner-btn pm-page-scroll"></a>
        </div>
                    
        <div class="pm-banner-overlay"></div>
        <div class="pm-pattern-overlay" id="pm-pattern-overlay"></div>
                
                
    </section>
    <!-- /Header area end -->
                    
    <!-- BODY CONTENT starts here -->
    
    <!-- PANEL 1 -->
    <article class="main" id="article">
        <div class="container home pm-containerPadding80">
    
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 newsletter-body">
                
                	<?php //echo do_shortcode($post->post_content); ?>
                    
                    <?php if (have_posts()) :  while (have_posts()) : the_post(); ?>
                
						<?php the_content(); ?>
                        
                    <?php endwhile; else: ?>
                    <?php endif; ?>
                    
                </div><!-- /.col -->
            </div><!-- /.row -->
            
            <?php if(is_array($pm_call_to_actions)) : ?>
            
            	<div class="row row-eq-height">
            
            		<?php foreach($pm_call_to_actions as $val) { ?>
                    
                    	<div class="col-lg-6 col-md-6 col-sm-12 pm-align-center pm-containerPadding20 cta-box">
                        
                            
                                <p>
									<?php echo esc_attr($val['message']); ?>
                                
                                
                                </p>
                                
                                <div class="cta-box-btn-container">
									<?php if( ICL_LANGUAGE_CODE ) { ?>
                            
                                        <?php 
                                        
                                            switch(ICL_LANGUAGE_CODE) {
                                                
                                                case "en" :									
                                                    ?>
                                                        <a href="<?php echo esc_url($val['url']); ?>" class="cta-box-btn"><i class="fa fa-envelope"></i> <?php esc_attr_e('Click Here', 'newslettermanagement'); ?></a>
                                                    <?php								
                                                break;
                                                
                                                case "fr";									
                                                    ?>
                                                        <a href="<?php echo esc_url($val['url']); ?>" class="cta-box-btn"><i class="fa fa-envelope"></i> Cliquez ici</a>
                                                    <?php									
                                                break;
                                                
                                            }
                                        
                                        ?>                        	
                                    
                                    <?php } else { ?>
                                    
                                        <a href="<?php echo esc_url($val['url']); ?>" class="cta-box-btn"><i class="fa fa-envelope"></i> <?php esc_attr_e('Click Here', 'newslettermanagement'); ?></a>
                                    
                                    <?php } ?> 
                                </div>
                            
                            
                        </div>
                    
                    <?php } ?>
                
                </div><!-- /.row -->
                
            <?php endif; ?>
            
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