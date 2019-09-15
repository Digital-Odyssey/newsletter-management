<?php

/*
Plugin Name: Newsletter Management
Plugin URI: http://www.pulsarmedia.ca
Description: Manage newsletter campaigns easily through WordPress
Version: 1.0
Author: Pulsar Media
Author URI: http://www.pulsarmedia.ca
License: 
*/

//Define global constants
if ( ! defined( 'PM_PLUGIN_URL' ) ) {
	define( 'PM_PLUGIN_URL', plugin_dir_url(__FILE__) );	
}
if ( ! defined( 'PM_PLUGIN_PATH' ) ) {
	define( 'PM_PLUGIN_PATH', plugin_dir_path(__FILE__) );
}
if ( ! defined( 'PM_ADMIN_URL' ) ) {
  define( 'PM_ADMIN_URL', PM_PLUGIN_URL . 'admin');
}
if ( ! defined( 'PM_FRONT_URL' ) ) {
  define( 'PM_FRONT_URL', PM_PLUGIN_URL . 'front-end' );
}
if ( ! defined( 'PM_DEBUG' ) ) {
  //true by default
  define( 'PM_DEBUG', true );
}


// Implicitly prevent the plugin's installation from collision
if ( !class_exists( 'NewsletterManager' ) ) {
	
	class NewsletterManager {		
			
		//Constructor
		public function __construct() {
			
			//Data user export
			if(isset($_POST['export-data'])){
				
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment;filename=newsletter_data.csv');
				header('Cache-Control: no-cache, no-store, must-revalidate');
				header('Pragma: no-cache');
				header('Expires: 0');
				
				global $wpdb;
			
				//start csv output
				$csvOutput = fopen('php://output', 'w');
				
				//create csv columns
				$csv_columns = $wpdb->get_row( "SELECT campaign_name, company_name, email_address, city, province, date_collected FROM {$wpdb->prefix}newsletter_email_data LIMIT 1", ARRAY_A );
				$csv_headers = array_keys($csv_columns);
				fputcsv($csvOutput, $csv_headers);
				
				//get user data
				$user_data = $wpdb->get_results( "SELECT campaign_name, company_name, email_address, city, province, date_collected FROM {$wpdb->prefix}newsletter_email_data", ARRAY_A );
				
				//append user data to csv file
				foreach ($user_data as $row) {
					fputcsv($csvOutput, $row);
				}
				
				//finish csv		
				fclose($csvOutput);
				exit;
			
			}
									
			//ACTIONS
			register_activation_hook( __FILE__, array( $this, 'pm_create_email_data_table' ) );
			register_activation_hook( __FILE__, array( $this, 'pm_create_user_groups_table' ) );
			register_activation_hook( __FILE__, array( $this, 'pm_create_mp_emails_table' ) );
			
			//Language support
			add_action( 'init', array( $this, 'load_languages' ) ); //LOAD LANGUAGE FILES FOR LOCALIZATION SUPPORT
			
			add_action( 'init', array( $this, 'add_post_type' ) ); //REGISTER THE POST TYPE
			add_action( 'admin_init', array( $this, 'add_post_metaboxes' ) ); //ADD POST TYPE META OPTIONS
			
			
			//add_action( 'init', array( $this, 'add_default_paypal_options' ) ); //ADD DEFAULT OPTIONS IF REQUIRED
			//add_action('admin_menu', array( $this, 'pm_newsletter_manager_settings' ) );// ADD SETTINGS PAGE
			
			add_action('admin_menu', array( $this, 'pm_collected_data_page' ) );// ADD COLLECTED DATA PAGE
			add_action('admin_menu', array( $this, 'pm_user_groups_page' ) );// USER GROUPS
			add_action('admin_menu', array( $this, 'pm_mp_emails_page' ) );// MP EMAILS - WORKS IN CONJUNCTION WITH USER GROUPS (Create table relationship)
			add_action('admin_menu', array( $this, 'pm_newsletter_settings_page' ) );
			add_action('admin_menu', array( $this, 'pm_general_help_page' ) );
			
			
			//Enqueue scripts for front-end
			add_action( 'wp_enqueue_scripts', array( $this, 'load_front_scripts' ) );
			
			//Google fonts preconnect
			add_filter( 'wp_resource_hints', array( $this, 'pm_ln_resource_hints' ), 10, 2 );
			
			
			//Save data action
			add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 2 );
			
			//Enqueue scripts for admin
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );//ADD STYLES & SCRIPTS FOR ADMIN	
			
			//AJAX
			add_action('wp_ajax_send_data_form', array( $this, 'pm_ln_send_data_form' ));
			add_action('wp_ajax_nopriv_send_data_form', array( $this, 'pm_ln_send_data_form' ));
						
			//this is wordpress ajax that can work in front-end and admin areas
			//add_action('wp_ajax_nopriv_your_ajax', array( $this, 'shortcode_ajax_function' ) );//_your_ajax is the action required for jQuery Ajax setting
			//add_action('wp_ajax_your_ajax', array( $this, 'shortcode_ajax_function' ));//_your_ajax is the action required for jQuery Ajax setting
			
			//FILTERS
			add_filter( 'template_include', array( $this, 'pm_ln_load_newsletter_template' ) );
			
			//add widget text shortcode support
			//add_filter( 'widget_text', 'do_shortcode' );
			//add_filter( 'the_content', 'do_shortcode' );
			
			
		}//end of constructor
		
		//Load language file(s) (.mo)
		public function load_languages() { 
			load_plugin_textdomain( 'newslettermanagement', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
		} 
		
		public function pm_ln_load_newsletter_template( $template ) {
			
			if ( 'otanewsletters' === get_post_type() ) {
				
				$post_id = get_the_ID();				
				$pm_newsletter_template_meta = get_post_meta( $post_id, 'pm_newsletter_template_meta', true );
				
								
				//run switch statement for selected tempalte
				switch($pm_newsletter_template_meta) {
					
					case "landing_page" :
						return dirname( __FILE__ ) . '/templates/template-newsletter.php';
					break;
					
					case "form_page" :
						return dirname( __FILE__ ) . '/templates/template-newsletter-form.php';
					break;
					
					default :
						return $template;
					break;
						
				}			
				
			}
		
			return $template;
		}
		
		
		//TABLE CREATION FOR EMAIL DATA COLLECTION (https://codex.wordpress.org/Creating_Tables_with_Plugins)
		public function pm_create_email_data_table () {

		    global $wpdb;
					
		    $table_name = $wpdb->prefix . "newsletter_email_data"; 
		
		    $charset_collate = $wpdb->get_charset_collate();
		
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  date_collected timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			  campaign_name tinytext NOT NULL,
			  company_name tinytext NOT NULL,
			  email_address varchar(255) NOT NULL,
			  city tinytext NOT NULL,
			  province tinytext NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";
		
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );	
		
		}
		
		public function pm_create_user_groups_table () {

		    global $wpdb;
					
		    $table_name = $wpdb->prefix . "newsletter_user_groups"; 
		
		    $charset_collate = $wpdb->get_charset_collate();
		
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  date_created timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			  group_name tinytext NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";
		
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );	
		
		}
		
		public function pm_create_mp_emails_table () {

		    global $wpdb;
					
		    $table_name = $wpdb->prefix . "newsletter_mp_emails"; 
		
		    $charset_collate = $wpdb->get_charset_collate();
		
			$sql = "CREATE TABLE $table_name (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  group_id mediumint(9) NOT NULL,
			  date_created timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			  email_address varchar(255) NOT NULL,
			  PRIMARY KEY  (id),
			  FOREIGN KEY (group_id) REFERENCES {$wpdb->prefix}newsletter_user_groups (id) ON DELETE CASCADE
			) $charset_collate;";
		
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );	
		
		}
		
		
		//For testing purposes
		/*function pm_create_email_data_table_install_data() {
			
			global $wpdb;
			
			$company_name = 'ABC Trucking';
			$email_address = 'leo@pulsarmedia.ca';
			$city = 'Toronto';
			$province = 'Ontario';
			
			$table_name = $wpdb->prefix . 'collected_email_data';
			
			$wpdb->insert( 
				$table_name, 
				array( 
					'date_collected' => current_time( 'mysql' ), 
					'company_name' => $company_name, 
					'email_address' => $email_address,
					'city' => $city,
					'province' => $province, 
				) 
			);
			
		}*/
		
		public function pm_ln_send_data_form() {
			
			 // Verify nonce
			 if( isset( $_POST['pm_send_data_form_nonce'] ) ) {
			
			   if ( !wp_verify_nonce( $_POST['pm_send_data_form_nonce'], 'pm_ln_nonce_action' ) ) {
				   die( 'A system error has occurred, please try again later.' );
			   }	   
			  
			 }
		
			 //Post values
			 $company_name = sanitize_text_field($_POST['company_name']);
			 $email_address = sanitize_text_field($_POST['email_address']);
			 $city = sanitize_text_field($_POST['city']);
			 $province = sanitize_text_field($_POST['province']);
			 $campaign_name = sanitize_text_field($_POST['campaign_name']);
			 $campaign_groups = $_POST['campaign_groups'];
			 $campaign_message = $_POST['campaign_message'];
			 $recaptcha_response = $_POST['recaptcha_response'];
			 	 			 
			
			 if ( empty($company_name) ){
				
				echo 'company_name_error';
				die();
		
			} elseif( $this->pm_ln_validate_email($email_address) == false ){
				
				echo 'email_error';
				die();
				
			} elseif( empty($city) ){
				
				echo 'city_error';
				die();
				
			} elseif( empty($province) ){
				
				echo 'province_error';
				die();
				
			} elseif(empty($recaptcha_response)) {
				
				echo 'recaptcha_error';
				die();
				
			}
			
			//All good, save data to database
			global $wpdb;
						
			$table_name = $wpdb->prefix . 'newsletter_email_data';
			
			$save_data = $wpdb->insert( 
				$table_name, 
				array( 
					//'date_collected' => current_time( 'mysql' ), 
					'campaign_name' => $campaign_name, 
					'company_name' => $company_name, 
					'email_address' => $email_address,
					'city' => $city,
					'province' => $province, 
				) 
			);
			
			
			$emails_array = array();
			
			if( is_array($campaign_groups) ) {
				
				if( count($campaign_groups) > 0 ) {									
					
					//Retrieve emails from database
					$len = count($campaign_groups);
					
					
					for( $i = 0; $i < $len; $i++ ) {
						
						$group_id = $campaign_groups[$i];
						
						$group_results = $wpdb->get_results( "SELECT g.*, e.email_address FROM {$wpdb->prefix}newsletter_user_groups g INNER JOIN {$wpdb->prefix}newsletter_mp_emails e ON g.id = e.group_id WHERE g.id = {$group_id}", ARRAY_A );
						
						//parse emails
						if( is_array($group_results) ) {
							
							foreach($group_results as $result) {
								
								$recipient = $result['email_address'];
								
								array_push($emails_array, $recipient);							
								
							}
							
						}//end if parse emails
						
					}//end for loop						
					
				}
				
			}	
			
			//All good, send email(s)
			if(count($emails_array) > 0) {
				
				$newsletter_disclaimer_eng = get_option('newsletter_disclaimer_eng_setting');
				$newsletter_disclaimer_fre = get_option('newsletter_disclaimer_fre_setting');
				
				$headers[] = 'MIME-Version: 1.0' . "\r\n";
				$headers[] = 'Content-type: text/html; charset=utf-8' . "\r\n";
				$headers[] = 'From: '.esc_attr__('donotreply', 'otanewsletters').'@'. $_SERVER['SERVER_NAME'] .' <donotreply@'. $_SERVER['SERVER_NAME'] .'>' . "\r\n";
				
				$emails_length = count($emails_array);
				
				for($i = 0; $i < $emails_length; $i++) {
					
					if($i > 0) {
						
						$headers[] = 'Bcc: ' . $emails_array[$i];
						
					}
					
				}
				
				
				//Send email to recipient
				$subj = $campaign_name;	
				
				$body = nl2br($campaign_message);
				  
				//ADD FRENCH TRANSLATIONS!!!
				if( ICL_LANGUAGE_CODE ) {
					
					switch(ICL_LANGUAGE_CODE) {
						
						case "en" :
							
							$body .=  '<br><br>' . esc_html__('Company Name', 'otanewsletters') .': '.$company_name.'<br>' .'
							  '. esc_html__('Email Address', 'otanewsletters') .': '.$email_address.'<br>' .'
							  '. esc_html__('City', 'otanewsletters') .': '.$city.'<br>' .'
							  '. esc_html__('Province', 'otanewsletters') .': '.$province.'<br>' .'
							';					
							
							$body .= '<br><br>' . $newsletter_disclaimer_eng;	
						
						break;
						
						case "fr" :
						
							$body .=  '<br><br>Nom de la compagnie: '.$company_name.'<br>' .'
							  Adresse e-mail: '.$email_address.'<br>' .'
							  Ville: '.$city.'<br>' .'
							  Province: '.$province.'<br>' .'
							  
							';					
							
							$body .= '<br><br>' . $newsletter_disclaimer_fre;	
						
						break;
						
					}//end switch
					
				} else {
					
					$body .=  '<br><br>' . esc_html__('Company Name', 'otanewsletters') .': '.$company_name.'<br>' .'
					  '. esc_html__('Email Address', 'otanewsletters') .': '.$email_address.'<br>' .'
					  '. esc_html__('City', 'otanewsletters') .': '.$city.'<br>' .'
					  '. esc_html__('Province', 'otanewsletters') .': '.$province.'<br>' .'
					  
					';					
					
					$body .= '<br><br>' . $newsletter_disclaimer_eng;
					
				}
				
				$send_mail = wp_mail( $emails_array[0], $subj, stripslashes($body), $headers );	
				
				if($send_mail) {
								
					echo 'success';
					die();
					
				} else {
					
					echo 'failed';
					die();
						
				}	
				
			} else {
				echo 'No emails found';
				die();	
			}			
			
			/*if($save_data) {
									
				echo 'success';
				die();
				
			} else {
				
				echo 'failed';
				die();
					
			}	*/
			
				
		}
		
		public function pm_ln_validate_email($email){
			
			return filter_var($email, FILTER_VALIDATE_EMAIL);
			
		}//end of validate_email()
		
		//GOOGLE FONTS PRE-CONNECT
		public function pm_ln_resource_hints( $urls, $relation_type ) {
			if ( wp_style_is( 'pm_ln_theme-fonts', 'queue' ) && 'preconnect' === $relation_type ) {
				$urls[] = array(
					'href' => 'https://fonts.gstatic.com',
					'crossorigin',
				);
			}
		
			return $urls;
		}
		
		//GOOGLE FONTS
		public function pm_ln_fonts_url() {
		
			$fonts_url = '';
			 
			$Montserrat_font = _x( 'on', 'Montserrat font: on or off', 'newslettermanagement' );
			$Open_sans_font = _x( 'on', 'Open Sans font: on or off', 'newslettermanagement' );
			$Source_sans_pro_font = _x( 'off', 'Source Sans Pro font: on or off', 'newslettermanagement' );
			$Merrirweather_font = _x( 'off', 'Merrirweather font: on or off', 'newslettermanagement' );
			$Worksans_font = _x( 'off', 'Worksans font: on or off', 'newslettermanagement' );
			$Lato_font = _x( 'off', 'Lato font: on or off', 'newslettermanagement' );
			
			$font_families = array();
		
			if ( 'off' !== $Montserrat_font ) {
				$font_families[] = 'Montserrat:400,700';
			}
			
			if ( 'off' !== $Open_sans_font ) {
				$font_families[] = 'Open Sans:400,300,600';
			}
			
			if ( 'off' !== $Merrirweather_font ) {
				$font_families[] = 'Merriweather:300,300i,400,400i,700,700i,900,900i';
			}
			
			if ( 'off' !== $Worksans_font ) {
				$font_families[] = 'Work+Sans:100,200,300,400,500,600,700,800,900';
			}
			
			if ( 'off' !== $Source_sans_pro_font ) {
				$font_families[] = 'Source Sans Pro:400,200,200italic,300,300italic,400italic,600,600italic,700,700italic,900,900italic';
			}
			
			if ( 'off' !== $Lato_font ) {
				$font_families[] = 'Lato:100,100i,300,300i,400,400i,700,700i';
			}		
			
			$query_args = array(
				'family' => urlencode( implode( '|', $font_families ) ),
				'subset' => urlencode( 'latin,latin-ext' ),
			);
		
			$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
		
			return esc_url_raw( $fonts_url );
		}
		
		
		
		public function pm_newsletter_settings_page() {
			add_submenu_page( 'edit.php?post_type=otanewsletters', __('Form Settings'),  __('Form Settings'), 'manage_options', 'pm_newsletter_settings',  array( $this, 'pm_newsletter_settings' ) );
		}
		
		public function pm_newsletter_settings() {	
		
			if( isset( $_POST['save_plugin_settings'] ) ) {
				
				update_option('newsletter_disclaimer_eng_setting', sanitize_textarea_field($_POST["newsletter_disclaimer_eng_setting"]));
				update_option('newsletter_disclaimer_fre_setting', sanitize_textarea_field($_POST["newsletter_disclaimer_fre_setting"]));
				update_option('google_recaptcha_site_key', sanitize_text_field($_POST["google_recaptcha_site_key"]));
				
				echo '<div id="message" class="updated fade"><h4>Your settings have been saved.</h4></div>';
				
			}
		
			$newsletter_disclaimer_eng_setting = get_option('newsletter_disclaimer_eng_setting');
			$newsletter_disclaimer_fre_setting = get_option('newsletter_disclaimer_fre_setting');
			$google_recaptcha_site_key = get_option('google_recaptcha_site_key');
		
			?>
            
            	<div class="wrap">
            
            	<h2><?php _e('Form Settings', 'otanewsletters') ?></h2>
                
                <form action="<?php the_permalink(); ?>" method="post">
                        
                    <div class="user-groups-container">
                    
                    	<label class="block-label">Google Re-captcha site key:</label>
                        <input type="text" name="google_recaptcha_site_key" class="regular-text" value="<?php echo esc_attr($google_recaptcha_site_key); ?>" />
                        
                        <br /><br />
                    
                        <label class="block-label">Email Disclaimer (English):</label>
                        <textarea name="newsletter_disclaimer_eng_setting" rows="5" class="admin-textarea"><?php echo esc_attr($newsletter_disclaimer_eng_setting); ?></textarea>
                        
                        <br /><br />
                        
                        <label class="block-label">Email Disclaimer (French):</label>
                        <textarea name="newsletter_disclaimer_fre_setting" rows="5" class="admin-textarea"><?php echo esc_attr($newsletter_disclaimer_fre_setting); ?></textarea>
                        
                        <br /><br />
                        <input type="submit" value="Save Settings" class="button button-primary" />
                        
                    </div>
                    
                    <input type="hidden" name="save_plugin_settings" />
                
                </form>
                
                
            
            <?php
		
		}
		
		public function pm_general_help_page() {
			add_submenu_page( 'edit.php?post_type=otanewsletters', __('Documentation'),  __('Documentation'), 'manage_options', 'pm_general_help',  array( $this, 'pm_general_help' ) );
		}
		
		public function pm_general_help() {	
		
			?>
            
            	<div class="wrap">
            
            	<h2><?php _e('Documentation', 'otanewsletters') ?></h2>
                
                <br />
                
                <ol>
                
                	<li>
                    	<button id="creating_a_newsletter" class="button button-primary documentation-button">Creating a Newsletter</button>
                
                        <div class="docuementation_content" id="creating_a_newsletter_content">
                        
                            <p>Newsletters can be created under the section labelled <strong>Create Newsletter</strong>.</p>
                            
                            <p>Here are some general tips to keep in mind when creating a newsletter:</p>
                            
                            <ol>
                            
                            	<li>Be sure to assign the appropriate <strong>Newsletter Template</strong> under the <strong>Post Attributes</strong> panel. Each template is designed for specific purposes. There are currently two templates to choose from each labelled <strong>Newsletter Template (Landing Page)</strong> and <strong>Newsletter Template (Form)</strong>. The <strong>Landing Page</strong> template is designed to be the main landing page for your newsletter campaign and the <strong>Form</strong> template is designed to send and collect data from the end user.</li>
                                
                                <li>
                                    <p>Certain post options are designed to work with either the <strong>Landing Page</strong> template or the <strong>Form</strong> template only. </p>
                                    <p>Options that are designed to work with the <strong>Landing Page</strong> template will have the following message included in italics:</p>
                                    <p><i>Applies to the "Landing Page" template only.</i></p>
                                    <p>Options that are designed to work with the <strong>Form</strong> template will have the following message included in italics:</p>
                                    <p><i>Applies to the "Form" template only.</i></p>
                                </li>
                                
                                <li>It is a good idea to assign a <strong>Campaign Name</strong> to both the landing page and form page for your newsletter campaign. This will allow you to view and sort campaigns more easily under the <strong>All Newsletters</strong> area.</li>
                                
                                
                                
                                <li>To translate your newsletter pages we recommend using the WPML plugin. Once the plugin is installed and active you will be able to duplicate your newsletter pages into translated pages from the <strong>All Newsletters</strong> section. Each post will contain a "plus" icon for each language defined under the WPML settings. Simply pressing the "plus" icon will create a translated post after which you can proceed with inserting your translated content. WPML also gives you the option to copy content from the parent counterpart allowing you to preserve your content layout in the visual editor.</li>
                                
                                <li>The Display Translation post option will only work if the WPML plugin is installed and activated. The translation button was designed with WPML to automatically detect page translations.</li>
                                
                            </ol>
                            
                        </div>
                    </li>
                    
                    <li>
                    	<button id="managing_newsletters" class="button button-primary documentation-button">Managing Newsletters</button>
                
                        <div class="docuementation_content" id="managing_newsletters_content">
                            
                            <p>Newsletters can be managed under the section labelled <strong>All Newsletters</strong>. Under this section newsletters can br viewed and organized by title, author, campaign name or date. Newsletter entries can be quickly edited, deleted or viewed and you can also create one more multiple tranlsations for each newsletter with the WPML plugin.</p>
                            
                        </div>
                    </li>
                    
                    <li>
                    	<button id="creating_campaign_names" class="button button-primary documentation-button">Creating Campaign Names</button>
                
                        <div class="docuementation_content" id="creating_campaign_names_content">
                            <p>The section labelled <strong>Campaign Names</strong> acts as a category list and can be used to create campaign names for your newsletter campaigns. Campaign Names can be used to keep your newsletter campaigns more organized.</p>
                            
                            <p><strong>IMPORTANT!</strong> Campaign Names are also assigned to the outgoing email subject line. If a Campaign Name is not assigned to the Form template then the subject line will remain blank.</p>
                        </div>
                    </li>
                    
                    <li>
                    	<button id="managing_collected_data" class="button button-primary documentation-button">Managing Collected Data</button>
                
                        <div class="docuementation_content" id="managing_collected_data_content">
                            <p>All data submitted through the Form template is collected and stored under the section labelled <strong>Collected Data</strong>. The data is displayed in a table format and can be exported to a simple CSV file. Additional actions include deleting individual data entries or purging all data entries at once.</p>
                        </div>
                    </li>
                    
                    <li>
                    	<button id="managing_user_groups" class="button button-primary documentation-button">Managing User Groups</button>
                
                        <div class="docuementation_content" id="managing_user_groups_content">
                            <p>User Groups are designed to work in conjunction with the Form template and can be managed under the section labelled <strong>Manage User Groups</strong>. User Groups act as a container for assigning one or more email addresses under the <strong>Manage Group Emails</strong> section. Once one or more User Groups have been created those User Groups will appear on the post options and can be checked on or off. User Group(s) that have been checked on will receive the outgoing email from the form submission.</p>
                        </div>
                    </li>
                    
                    <li>
                    	<button id="managing_group_emails" class="button button-primary documentation-button">Managing Group Emails</button>
                
                        <div class="docuementation_content" id="managing_group_emails_content">
                            <p>User Groups can be assigned one or more email addresses under the section labelled <strong>Manage Group Emails</strong>. Email addresses can only be assigned if one or more User Groups have been created. Each User Group will only allow unique email addresses to be inserted. For example the email address john.smith@example.com can only be assigned once to a particular User Group however that same email address can be assigned to other User Groups if required.</p>
                        </div>
                    </li>
                
                </ol>
                
            
            <?php
		
		}

		
		public function pm_mp_emails_page() {
			add_submenu_page( 'edit.php?post_type=otanewsletters', __('Manage Group Emails'),  __('Manage Group Emails'), 'manage_options', 'newsletter_mp_emails',  array( $this, 'pm_mp_emails' ) );
		}
		
		public function pm_mp_emails() {	
		
			global $wpdb;
			$table_name = $wpdb->prefix . 'newsletter_mp_emails';
			
			//form checks
			$assignedToGroup = false;
			$assignedToGroupName = '';
			
			if (isset($_POST['create_new_mp_email'])) {
				
				//collect data and save
				$email_address = sanitize_text_field( $_POST['form_mp_email_address'] );
				$group_id = sanitize_text_field( $_POST['form_mp_email_group'] );
				
				//check if email address is already assigned to the selected user group first
				if( !empty($email_address) && $group_id !== 'default') {
					
					$check_email_entry = $wpdb->get_row( "SELECT group_id, email_address FROM {$wpdb->prefix}newsletter_mp_emails WHERE group_id = {$group_id} AND email_address = '{$email_address}' LIMIT 1", ARRAY_A );
					
					//print_r($check_email_entry);
				
					if($check_email_entry['email_address'] === $email_address && $check_email_entry['group_id'] === $group_id) {
											
						$assignedToGroup = true;
						$groupName = $wpdb->get_row( "SELECT group_name FROM {$wpdb->prefix}newsletter_user_groups WHERE id = {$group_id} LIMIT 1", ARRAY_A );
						$assignedToGroupName = $groupName['group_name'];
						
					} else {
						
						//save to database							
						$save_data = $wpdb->insert( 
							$table_name, 
							array( 
								//'date_collected' => current_time( 'mysql' ), 
								'email_address' => $email_address,
								'group_id' => $group_id
							) 
						);							
						
					}
					
				}
				
			}//end of create new email entry
			
			//Delete email
			$email_deleted = false;
			
			if( isset($_POST['delete_mp_email']) ) {
					
				$delete_email = $wpdb->delete( 
					$table_name, 
					array( 'ID' => $_POST['delete_mp_email'] ), 
					array( '%d' ) 
				);
				
				if($delete_email) {
					$email_deleted = true;
				}
					
			}
			
			
			?>
            
            <div class="wrap">
            
            <h2><?php _e('Manage Group Emails', 'otanewsletters') ?></h2>
            
            <?php 
			
				//check if user groups exist first
				$check_user_groups = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}newsletter_user_groups LIMIT 1", ARRAY_A );
				
				//print_r($check_user_groups);
			
			?>
            
            <?php 
			
				if($email_deleted) {
					echo '<br><b class="entry-deleted">Email address successfully removed.</b>';	
				}
			
			?>
            
            <?php if( $check_user_groups ) { ?>
            
            	<?php if($assignedToGroup) : ?>
            
                    <p>That email address is already assigned to the group <b><?php echo $assignedToGroupName ?></b>.</p>
                
                <?php endif; ?>
                
                <form action="<?php the_permalink(); ?>" method="post">
                        
                    <div class="user-groups-container">
                        <label>Email Address:
                        <input type="text" value="" class="regular-text" name="form_mp_email_address" />
                        <select name="form_mp_email_group" class="mp-email-group-select-list">
                            <option value="default">-- User Group --</option>
                            <?php 
                                //get user groups data
                                $group_results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}newsletter_user_groups", ARRAY_A );
                            ?>
                        
                            <?php foreach($group_results as $group) { ?>
                            
                                <option value="<?php echo $group['id'] ?>"><?php echo $group['group_name'] ?></option>
                            
                            <?php } ?>
                        
                        </select>
                        <input type="submit" value="Assign Email" class="button button-primary" />
                        </label>
                    </div>
                    
                    <input type="hidden" name="create_new_mp_email" />
                
                </form>
                
                <?php
				
				$mp_results = $wpdb->get_results( "SELECT mp_emails.*, user_groups.group_name FROM {$wpdb->prefix}newsletter_mp_emails AS mp_emails INNER JOIN {$wpdb->prefix}newsletter_user_groups AS user_groups ON mp_emails.group_id = user_groups.id ORDER BY user_groups.group_name", ARRAY_A );
				
				if( is_array($mp_results) ) {
			
					if( count($mp_results) > 0 ) {
						
						//print_r($mp_results);
						
						?>
						
						<hr> 
						<h2>Existing Group Emails</h2>
						
						<form action="<?php the_permalink(); ?>" method="post" id="delete_mp_email_form">
						
							<div class="table100 ver1 m-b-110">
							
								<table data-vertable="ver1">
							
									<thead>
										<tr class="row100 head">
											<!--<th class="column100 column1"></td>-->
											<th class="column100 column1">User Group</td>
											<th class="column100 column1">Email Address</td>
											<th class="column100 column1"></th>
										</tr>
									</thead>
							
									<tbody>
										<?php foreach( $mp_results as $result ) { ?>
																	
											<tr class="row100">
												<!--<td class="td-centered" width="7%"><?php //echo $data['id']; ?></td>-->
												<td><?php echo $result['group_name']; ?></td>
												<td><?php echo $result['email_address']; ?></td>
												<td width="7%" align="center"><a href="#" class="button button-primary delete-mp-email-btn" data-id="<?php echo $result['id'] ?>">Delete</a></td>
											</tr>
										
										<?php }//end foreach ?>
									</tbody>
								
								</table>
							
							</div><!-- /.div -->
													
							<input type="hidden" name="delete_mp_email" id="delete_mp_email" value="0" />
						
						</form>
						
						<?php
						
					} else {
						
						echo '<hr> No existing emails found in the database.';
						
					}
					
				}
				
				?>
            
            <?php } else { ?>
            
            	<p>No user groups found. At least one user group must exist before emails can be assigned.</p>
            
            <?php } ?>
                 
            
        <?php	
			
			
		}
		
		public function pm_user_groups_page() {
			add_submenu_page( 'edit.php?post_type=otanewsletters', __('Manage User Groups'),  __('Manage User Groups'), 'manage_options', 'newsletter_user_groups',  array( $this, 'pm_user_groups' ) );
		}
		
		public function pm_user_groups() {	
		
			global $wpdb;
			$table_name = $wpdb->prefix . 'newsletter_user_groups';
			
			if (isset($_POST['create_new_user_group'])) {
				
				//collect data and save
				$group_name = sanitize_text_field( $_POST['form_user_group'] );
				
				//save to database
				if(!empty($group_name)) {
					
					$save_data = $wpdb->insert( 
						$table_name, 
						array( 
							//'date_collected' => current_time( 'mysql' ), 
							'group_name' => $group_name
						) 
					);
					
				}				
				
				/*if($save_data) {
					
					echo 'success';
					die();
					
				} else {
					
					echo 'failed';
					die();
						
				}*/
				
			}
			
			$group_deleted = false;
			
			if (isset($_POST['delete_user_group'])) {
				
				//echo 'delete user group ' . $_POST['delete_user_group'];
				
				$delete_group = $wpdb->delete( 
					$table_name, 
					array( 'ID' => $_POST['delete_user_group'] ), 
					array( '%d' ) 
				);
				
				if($delete_group) {
					$group_deleted = true;	
				}
				
			}
			
			?>
            
            <div class="wrap">
                
				<h2><?php _e('Manage User Groups', 'otanewsletters') ?></h2>
                
                <?php 
				
					if($group_deleted) {
						echo '<br><b class="entry-deleted">User Group successfully removed.</b>';	
					}
				
				?>
                
                <br />
                
                <!--<button type="submit" class="button button-primary" id="create_new_user_group_btn">Create a User Group</button>-->
                
                <form action="<?php the_permalink(); ?>" method="post">
                    
                    <div class="user-groups-container">
                    	<label>User Group Name:
                    	<input type="text" value="" class="regular-text" name="form_user_group" />
                        <input type="submit" value="Create Group" class="button button-primary" /></label>
                    </div>
                    
                    <input type="hidden" name="create_new_user_group" />
                
                </form>
                
                <!-- Check for existing groups -->
                <?php 
					global $wpdb;
					$group_results = $wpdb->get_results( "SELECT g.*, (SELECT COUNT(e.group_id) FROM {$wpdb->prefix}newsletter_mp_emails AS e WHERE e.group_id = g.id) AS assigned_emails FROM {$wpdb->prefix}newsletter_user_groups g", ARRAY_A );
					
					//print_r($group_results);
					
					if( is_array($group_results) ) {
					
						if( count($group_results) > 0 ) {
							
							//display existing groups with delete option
							
							echo '<hr> <h2>Existing Groups</h2>';
							
							echo '<form action="'. get_the_permalink() .'" method="post" id="delete_user_group_form">';
								
								?>
                                
                                <div class="table100 ver1 m-b-110">
							
                                    <table data-vertable="ver1">
                                
                                        <thead>
                                            <tr class="row100 head">
                                                <!--<th class="column100 column1"></td>-->
                                                <th class="column100 column1">User Group</td>
                                                <th class="column100 column1">Assigned Emails</td>
                                                <th class="column100 column1"></th>
                                            </tr>
                                        </thead>
                                
                                        <tbody>
                                            <?php foreach( $group_results as $group ) { ?>
                                                                        
                                                <tr class="row100">
                                                    <!--<td class="td-centered" width="7%"><?php //echo $data['id']; ?></td>-->
                                                    <td><?php echo $group['group_name']; ?></td>
                                                    <td><?php echo $group['assigned_emails']; ?></td>
                                                    <td width="7%" align="center"><a href="#" class="button button-primary delete-user-group-btn" data-id="<?php echo $group['id'] ?>">Delete</a></td>
                                                </tr>
                                            
                                            <?php }//end foreach ?>
                                        </tbody>
                                    
                                    </table>
                                
                                </div><!-- /.div -->
                                
                                <?php

								
								echo '<input type="hidden" name="delete_user_group" id="delete_user_group" value="0" />';
							
							echo '</form>';
							
						} else {
							
							echo '<hr> No existing groups found in the database.';
							
						}
						
					}
					
					
				?>
                
            </div>
            
            <?php
		
		}
		
		
		//Add sub menus
		public function pm_collected_data_page() {
	
			//create custom top-level menu
			//add_menu_page( 'Framework Documentation', 'Theme Documentation', 'manage_options', __FILE__, 'pm_documentation_main_page',	plugins_url( '/images/wp-icon.png', __FILE__ ) );
			
			//create sub-menu items
			add_submenu_page( 'edit.php?post_type=otanewsletters', __('Collected Data'),  __('Collected Data'), 'manage_options', 'newsletter_data',  array( $this, 'pm_collected_data' ) );
			
			//create an options page under Settings tab
			//add_options_page('My API Plugin', 'My API Plugin', 'manage_options', 'pm_myplugin', 'pm_myplugin_option_page');	
		}
		
		
		//Show collected data
		public function pm_collected_data() {						
			
			//get data
			global $wpdb;
			
			$table_name = $wpdb->prefix . 'newsletter_email_data';
			
			$data_entry_deleted = false;
			
			if( isset($_POST['delete_collected_data_entry']) ) {
				
				//echo 'Delete data entry submitted = ' . $_POST['delete_collected_data_entry'];
					
				$delete_entry = $wpdb->delete( 
					$table_name, 
					array( 'ID' => $_POST['delete_collected_data_entry'] ), 
					array( '%d' ) 
				);
				
				if($delete_entry) {
					$data_entry_deleted = true;	
				}
					
			}
			
			$data_purged = false;
			
			if( isset($_POST['delete_collected_data']) ) {
				
				//echo 'Delete all data submitted';
					
				$purge_data = $wpdb->query("TRUNCATE TABLE $table_name");
				
				if($purge_data) {
					$data_purged = true;	
				}
					
			}
			
			//Get data
			$data_results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}newsletter_email_data ORDER BY campaign_name", ARRAY_A );
									
			?>
			
			<div class="wrap">
                
				<h2><?php _e('Collected Data', 'otanewsletters') ?></h2>
                
                <br />
                
                <?php
					if($data_entry_deleted) {
						echo '<br><b class="entry-deleted">Data entry successfully removed.</b>';	
					}
					
					if($data_purged) {
						echo '<br><b class="entry-deleted">All data entries have been successfully removed.</b>';	
					}
				?>
                
                <?php if ( is_array($data_results) ) { ?>
                
                	<?php if (count($data_results) > 0) { ?>
                    
                    	<form action="<?php echo get_permalink(); ?>" method="post">
                            <input type="submit" class="button button-primary" value="Export Data to CSV" />
                            <input type="hidden" name="export-data" />
                        </form>
                        
                        <br /><br />
                    
                        <div class="table100 ver1 m-b-110">
                        
                        	<form action="<?php the_permalink(); ?>" method="post" id="collected_data_form">
                            
                            	<table data-vertable="ver1">
                        
                                    <thead>
                                        <tr class="row100 head">
                                            <!--<th class="column100 column1"></td>-->
                                            <th class="column100 column1">Campaign Name</td>
                                            <th class="column100 column1">Company Name</td>
                                            <th class="column100 column1">Email Address</td>
                                            <th class="column100 column1">City</td>
                                            <th class="column100 column1">Province</td>
                                            <th class="column100 column1">Date Collected</td>
                                            <th class="column100 column1"></td>
                                        </tr>
                                    </thead>
                            
                                    <tbody>
                                        <?php foreach( $data_results as $data ) { ?>
                                                                    
                                            <tr class="row100">
                                                <!--<td class="td-centered" width="7%"><?php //echo $data['id']; ?></td>-->
                                                <td><?php echo $data['campaign_name']; ?></td>
                                                <td><?php echo $data['company_name']; ?></td>
                                                <td><?php echo $data['email_address']; ?></td>
                                                <td><?php echo $data['city']; ?></td>
                                                <td><?php echo $data['province']; ?></td>
                                                <td><?php echo date('M j Y g:i A', strtotime($data['date_collected'])); ?></td>
                                                <td width="7%" align="center"><a href="#" class="button button-primary delete-collected-data-entry-btn" data-id="<?php echo $data['id'] ?>">Delete</a></td>
                                            </tr>
                                        
                                        <?php }//end foreach ?>
                                    </tbody>
                                
                                </table>
                                
                                <input type="hidden" name="delete_collected_data_entry" id="delete_collected_data_entry" value="0" />
                            
                            </form>
                            
                            <br />
                            
                        	<form action="<?php the_permalink(); ?>" method="post" id="delete_all_collected_data_form">
                            
                            	<input type="submit" value="Purge all data" class="button button-primary" id="purge_all_collected_data_btn" />
                                <input type="hidden" name="delete_collected_data" />
                            
                            </form>
                            
                        
                        </div><!-- /.div -->
                    
                    <?php } else { ?>
                    
                    	<p>No data found in the database.</p>
                    
                    <?php } ?>
                
                <?php } ?>				
				
			</div>
			
			<?php
			
		}

		//Load admin scripts
		public function load_admin_scripts( $hook ) {
			
			$screen = get_current_screen();
      		$dot = ( PM_DEBUG ) ? '.' : '.min.';
						
			//print_r($screen);
						
			if ( is_admin() && $screen->post_type === "otanewsletters" ) { 
						
				//jQuery ui scripts
				wp_enqueue_script( 'jquery' );
				//wp_enqueue_script( 'jquery-ui-core' );
				//wp_enqueue_script( 'jquery-ui-mouse' );
				//wp_enqueue_script( 'jquery-ui-slider' );
				//wp_enqueue_script( 'jquery-ui-draggable' );
				//wp_enqueue_script( 'jquery-ui-dialog' );
				//wp_enqueue_script( 'jquery-ui-sortable' );
			
				//load styles and scripts
				wp_enqueue_style( 'styles', PM_ADMIN_URL . '/css/admin' . $dot . 'css' );
				
				//load the WP 3.5 Media uploader scripts and environment
				wp_enqueue_script('thickbox');  
        		wp_enqueue_style('thickbox');
				wp_enqueue_media( 'media-upload' );				
				
				//Load admin js file(s)
				wp_enqueue_script( 'wordpress-admin', PM_ADMIN_URL . '/js/wp-admin' . $dot . 'js' );
				wp_enqueue_script( 'image-uploader', PM_ADMIN_URL . '/js/media-uploader/pm-image-uploader' . $dot . 'js' );
				//wp_enqueue_script( 'tooltip', PM_ADMIN_URL . '/js/jquery.tooltip.class' . $dot . 'js' );
				//wp_enqueue_script( 'settings', PM_ADMIN_URL . '/js/premium-paypal-manager' . $dot . 'js' );
				
				//wp_enqueue_style( 'color-picker-styles', PM_ADMIN_URL . '/js/colorpicker/css/colorpicker' . $dot . 'css' );
				//wp_enqueue_script( 'color-picker', PM_ADMIN_URL . '/js/colorpicker/js/colorpicker' . $dot . 'js' );
				
				//wp_enqueue_style( 'spectrum-styles', PM_ADMIN_URL . '/js/spectrum/spectrum' . $dot . 'css' );
				//wp_enqueue_script( 'spectrum-picker', PM_ADMIN_URL . '/js/spectrum/spectrum' . $dot . 'js' );
				
				/*$newsletter_posts_args = array(
					'post_type' => 'otanewsletters',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'order' => 'DESC',
				);
			
				$newsletter_query = new WP_Query($newsletter_posts_args);*/
				
				$args = array(
					'sort_order' => 'asc',
					'sort_column' => 'post_title',
					'hierarchical' => 1,
					'exclude' => '',
					'include' => '',
					'meta_key' => '',
					'meta_value' => '',
					'authors' => '',
					'child_of' => 0,
					'parent' => -1,
					'exclude_tree' => '',
					'number' => '',
					'offset' => 0,
					'post_type' => 'otanewsletters',
					'post_status' => 'publish'
				); 
				$pages = get_pages($args);
				
				//Javascript Object	
				$wordpressOptionsArray = array( 
					'urls' => $pages,
				);
				
				wp_enqueue_script('wordpressAdminOptions', PM_ADMIN_URL . '/js/wordpress.js');
				wp_localize_script( 'wordpressAdminOptions', 'wordpressAdminOptionsObject', $wordpressOptionsArray );
			
			}//end of if
			
		}//end of load_scripts
			
		//Load front-end scripts
		public function load_front_scripts() {
			
			$dot = ( PM_DEBUG ) ? '.' : '.min.';
			
			if( get_post_type() === 'otanewsletters' ) {
				
				//load styles and scripts
				wp_enqueue_script( 'jquery' );
				
				//Google Fonts
				wp_enqueue_style( 'google-fonts', $this->pm_ln_fonts_url(), array(), null );
				
				//Google recaptcha
				wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js');
				
				//CSS
				wp_enqueue_style( 'bootstrap', PM_FRONT_URL . '/bootstrap3/css/bootstrap' . $dot . 'css' );
				wp_enqueue_style( 'master', PM_FRONT_URL . '/css/master' . $dot . 'css' );
				wp_enqueue_style( 'responsive', PM_FRONT_URL . '/css/responsive' . $dot . 'css' );
				wp_enqueue_style( 'fontawesome', PM_FRONT_URL . '/css/fontawesome/font-awesome' . $dot . 'css' );
				
				//JS
				wp_enqueue_script( 'bootstrap-js', PM_FRONT_URL . '/bootstrap3/js/bootstrap' . $dot . 'js' );
				wp_enqueue_script( 'modernizr-js', PM_FRONT_URL . '/js/modernizr.custom' . $dot . 'js' );
				wp_enqueue_script( 'ajax-form-js', PM_FRONT_URL . '/js/ajax-form/ajax-form' . $dot . 'js' );
				wp_enqueue_script( 'main-js', PM_FRONT_URL . '/js/main' . $dot . 'js' );
				
				
				/** Appointment form **/
				if( ICL_LANGUAGE_CODE ) {
					
					switch(ICL_LANGUAGE_CODE) {
						
						case "en" :
							
							$dataFormError1 = esc_attr__('Please provide your company name.', "newslettermanagement");
							$dataFormError2 = esc_attr__('Please provide a valid email address.', "newslettermanagement");
							$dataFormError3 = esc_attr__('Please provide your city.', "newslettermanagement");
							$dataFormError4 = esc_attr__('Please provide your province.', "newslettermanagement");
							$dataFormError5 = esc_attr__('Please verify that you are human.', "newslettermanagement");
							$successMessage = esc_attr__('Thank you for your submission.', "newslettermanagement");
							$failedMessage = esc_attr__('A system error occurred. Please try again later.', "newslettermanagement");
							$fieldValidation = esc_attr__('Validating fields...', "newslettermanagement");						
						
						break;
						
						case "fr" :
						
							$dataFormError1 = "Veuillez indiquer le nom de votre entreprise";
							$dataFormError2 = "Veuillez fournir une adresse email valide.";
							$dataFormError3 = "S'il vous plaît fournir votre ville.";
							$dataFormError4 = "S'il vous plaît fournir votre province.";
							$dataFormError5 = "S'il vous plaît vérifiez que vous êtes humain";
							$successMessage = "Merci pour votre soumission.";
							$failedMessage = "Une erreur système s'est produite. Veuillez réessayer plus tard.";
							$fieldValidation = "Validation des champs ...";
						
						break;
						
					}//end switch
					
				} else {
					
					$dataFormError1 = esc_attr__('Please provide your company name.', "newslettermanagement");
					$dataFormError2 = esc_attr__('Please provide a valid email address.', "newslettermanagement");
					$dataFormError3 = esc_attr__('Please provide your city.', "newslettermanagement");
					$dataFormError4 = esc_attr__('Please provide your province.', "newslettermanagement");
					$dataFormError5 = esc_attr__('Please verify that you are human.', "newslettermanagement");
					$successMessage = esc_attr__('Thank you for your submission.', "newslettermanagement");
					$failedMessage = esc_attr__('A system error occurred. Please try again later.', "newslettermanagement");
					$fieldValidation = esc_attr__('Validating fields...', "newslettermanagement");
					
				}
				
				//Define AJAX URL and pass to JS
				$js_file = PM_FRONT_URL . '/js/wordpress.js'; 
				
				wp_enqueue_script( 'pm_ln_register_script', $js_file );
					$array = array( 
						'pm_ln_ajax_url' => admin_url('admin-ajax.php'),
				);
					
				wp_localize_script( 'pm_ln_register_script', 'pm_ln_register_vars', $array );	
				
				//Javascript Object	
				$wordpressOptionsArray = array( 
					'urlRoot' => home_url(),
					'templateDir' => get_template_directory_uri(),
					'dataFormError1' => $dataFormError1,
					'dataFormError2' => $dataFormError2,
					'dataFormError3' => $dataFormError3,
					'dataFormError4' => $dataFormError4,
					'dataFormError5' => $dataFormError5,
					'successMessage' => $successMessage,
					'failedMessage' => $failedMessage,
					'fieldValidation' => $fieldValidation
				);
				
				wp_enqueue_script('wordpressOptions', PM_FRONT_URL . '/js/wordpress.js');
				wp_localize_script( 'wordpressOptions', 'wordpressOptionsObject', $wordpressOptionsArray );
				
			}
			
		}//end of load_front_scripts
				
		//REGISTER THE POST TYPE
		public function add_post_type() {
					
			$labels = array(
				'name' => 'Newsletters',
				'singular_name' => 'Newsletters',
				'add_new' => __( 'Create Newsletter', 'otanewsletters' ),
				'add_new_item' => __( 'Add Newsletter', 'otanewsletters' ),
				'edit_item' => __( 'Edit Newsletter', 'otanewsletters' ),
				'new_item' => __( 'Create Newsletter', 'otanewsletters' ),
				'all_items' => __( 'All Newsletters', 'otanewsletters' ),
				'view_item' => __( 'View Newsletter', 'otanewsletters' ),
				'search_items' => __( 'Search Newsletters', 'otanewsletters' ),
				'not_found' =>  __( 'No Newsletters found', 'otanewsletters' ),
				'not_found_in_trash' => __( 'No Newsletter found in Trash', 'otanewsletters' ), 
				'parent_item_colon' => '',
				'menu_name' => 'Newsletters'
			  );
		
			  $args = array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true, 
				'show_in_menu' => true, 
				//'show_in_admin_bar' => true,
				//'query_var' => true,
				'rewrite' => array( 'slug' => 'newsletter' ),
				'capability_type' => 'page',
				'has_archive' => false, 
				//'hierarchical' => true,
				'menu_position' => 5,
				'taxonomies' => array('newslettercampaigns'),
				'supports' => array( 'title', 'editor', 'author', 'revisions', 'page-attributes'), //'thumbnail'
				'menu_icon' => PM_ADMIN_URL . '/img/icon.png'
			  ); 
		
			  register_post_type( 'otanewsletters', $args );
			  
			  //Add category support
			  register_taxonomy('newslettercampaigns', 'otanewsletters', array(
				// Hierarchical taxonomy (like categories)
				'hierarchical' => true,
				'show_admin_column' => true,
				// This array of options controls the labels displayed in the WordPress Admin UI
				'labels' => array(
					'name' => _x( 'Campaigns', 'taxonomy general name' ),
					'singular_name' => _x( 'Campaign', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Campaigns' ),
					'all_items' => __( 'Popular Campaigns' ),
					'parent_item' => __( 'Parent Campaigns' ),
					'parent_item_colon' => __( 'Parent Campaign:' ),
					'edit_item' => __( 'Edit Campaigns' ),
					'update_item' => __( 'Update Campaigns' ),
					'add_new_item' => __( 'Add New Campaign' ),
					'new_item_name' => __( 'New Campaigns Name' ),
					'menu_name' => __( 'Campaign Names' ),
				),
				// Control the slugs used for this taxonomy
				'rewrite' => array(
					'slug' => 'newsletter_campaign', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			));
			
			//Add tag support
			/*register_taxonomy('pmpaypaltags', 'premiumpaypalmanager', array(
				// Hierarchical taxonomy (like categories)
				'hierarchical' => false,
				'show_admin_column' => true,
				// This array of options controls the labels displayed in the WordPress Admin UI
				'labels' => array(
					'name' => _x( 'Item Tag', 'taxonomy general name' ),
					'singular_name' => _x( 'Item Tags', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Item Tags' ),
					'all_items' => __( 'Popular Item Tags' ),
					'parent_item' => __( 'Parent Item Tags' ),
					'parent_item_colon' => __( 'Parent Item Tag:' ),
					'edit_item' => __( 'Edit Item Tag' ),
					'update_item' => __( 'Update Item Tag' ),
					'add_new_item' => __( 'Add New Item Tag' ),
					'new_item_name' => __( 'New Item Tag Name' ),
					'menu_name' => __( 'Item Tags' ),
				),
				// Control the slugs used for this taxonomy
				'rewrite' => array(
					'slug' => 'paypal_item_tags', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			));*/
			  
		  
		}//end of post type declaration
		
		//METABOXES for CPT
		public function add_post_metaboxes() {
			
			add_meta_box( 'pm_newsletter_template_meta', 'Newsletter Template', array( $this, 'pm_newsletter_template_meta_function' ) , 'otanewsletters', 'side', 'low' );
			
			add_meta_box( 'pm_banner_image_meta', 'Banner Image', array( $this, 'pm_banner_image_meta_function' ) , 'otanewsletters', 'normal', 'high' );
			
			add_meta_box( 'pm_banner_message_meta', 'Banner Message', array( $this, 'pm_banner_message_meta_function' ) , 'otanewsletters', 'normal', 'high' );
			
			add_meta_box( 'pm_show_translation_btn_meta', 'Display Translation Button?', array( $this, 'pm_show_translation_btn_meta_function' ) , 'otanewsletters', 'side', 'low' );
			
			add_meta_box( 'pm_show_logos_btn_meta', 'Display Logos?', array( $this, 'pm_show_logos_btn_meta_function' ) , 'otanewsletters', 'side', 'low' );
			
			add_meta_box( 'pm_call_to_action_boxes_meta', 'Call to actions', array( $this, 'pm_call_to_action_boxes_meta_function' ) , 'otanewsletters', 'normal', 'high' );			
			
			add_meta_box( 'pm_return_to_page_url_meta', 'Return to Previous Page URL', array( $this, 'pm_return_to_page_url_meta_function' ) , 'otanewsletters', 'normal', 'high' );
			
			
			
			add_meta_box( 'pm_form_message_meta', 'Form Message', array( $this, 'pm_form_message_meta_function' ) , 'otanewsletters', 'normal', 'high' );
			
			add_meta_box( 'pm_form_message_meta', 'Email Body Message', array( $this, 'pm_form_email_body_message_meta_function' ) , 'otanewsletters', 'normal', 'high' );
			
			add_meta_box( 'pm_user_groups_meta', 'User Groups', array( $this, 'pm_user_groups_meta_function' ) , 'otanewsletters', 'side', 'low' );
			
		}
		
		//BANNER IMAGE
		public function pm_banner_image_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$newsletter_banner_img = get_post_meta( $post->ID, 'newsletter_banner_img', true );
						
			?>
                
                <input type="text" value="<?php echo esc_html($newsletter_banner_img); ?>" name="newsletter_banner_img" id="newsletter_banner_img_field" class="pm-admin-upload-field" />
                <p class="form-notice-field"><?php esc_html_e('Recommended size: 1920x900px', 'newslettermanagement') ?></p>
                
                <input id="banner_upload_image_button" type="button" value="<?php esc_html_e('Media Library Image', 'newslettermanagement'); ?>" class="button-primary" />
                
                <div class="pm-banner-image-preview" id="pm_banner_image_preview">
                
                	<?php if($newsletter_banner_img) : ?>
                    
                    	<img src="<?php echo esc_html($newsletter_banner_img); ?>" />
                    
                    <?php endif; ?> 
                	
                </div>
                
                <?php if($newsletter_banner_img) : ?>
                    <input id="remove_banner_image_button" type="button" value="<?php esc_html_e('Remove Image', 'newslettermanagement'); ?>" class="button-primary" />
                <?php endif; ?> 
            
            <?php
			
			
		}
		
		//BANNER MESSAGE
		public function pm_banner_message_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$newsletter_banner_msg = get_post_meta( $post->ID, 'newsletter_banner_msg', true );
			
			?>
            	<p class="form-notice"><?php esc_html_e('Applies to the "Landing Page" template only.', 'newslettermanagement') ?></p>
                <input type="text" value="<?php echo esc_attr($newsletter_banner_msg); ?>" name="newsletter_banner_msg" class="form-field" />
                
            <?php
			
			
		}
		
		//RETURN URL
		public function pm_return_to_page_url_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_return_to_page_url_meta = get_post_meta( $post->ID, 'pm_return_to_page_url_meta', true );
			
			?>
            	<p class="form-notice"><?php esc_html_e('Applies to the "Form" template only.', 'newslettermanagement') ?></p>
                
                <?php			
				
					$newsletter_posts_args = array(
						'post_type' => 'otanewsletters',
						'post_status' => 'publish',
						'posts_per_page' => -1,
						'order' => 'DESC',
					);
				
					$newsletter_query = new WP_Query($newsletter_posts_args);
				
				?>
                
                <select name="pm_return_to_page_url_meta">
                
                	<?php if ($newsletter_query->have_posts()) : while ($newsletter_query->have_posts()) : $newsletter_query->the_post(); ?>
										
                        <?php $url = get_the_permalink(); ?>
                        
                        <option value="<?php echo $url ?>" <?php selected( $pm_return_to_page_url_meta, $url ) ?>><?php the_title() ?></option>
                    
                    <?php endwhile; else: endif; ?>
                    
                </select>
                
            <?php
			
			
		}
		
		//PAGE TEMPLATE
		public function pm_newsletter_template_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_newsletter_template_meta = get_post_meta( $post->ID, 'pm_newsletter_template_meta', true );
			
			?>
            	<p class="form-notice"><?php esc_html_e('Select your template layout.', 'newslettermanagement') ?></p>                
                
                <select name="pm_newsletter_template_meta">                
                	<option value="landing_page" <?php selected( $pm_newsletter_template_meta, 'landing_page' ) ?>>Newsletter Template (Landing Page)</option>
                    <option value="form_page" <?php selected( $pm_newsletter_template_meta, 'form_page' ) ?>>Newsletter Template (Form)</option>
                </select>
                
            <?php
			
			
		}
		
		//FORM MESSAGE
		public function pm_form_message_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_form_message_meta = get_post_meta( $post->ID, 'pm_form_message_meta', true );
			
			?>
            	<p class="form-notice"><?php esc_html_e('Applies to the "Form" template only.', 'newslettermanagement') ?></p>
                <input type="text" value="<?php echo esc_attr($pm_form_message_meta); ?>" name="pm_form_message_meta" class="form-field" />
                
            <?php
			
			
		}
		
		//EMAIL BODY MESSAGE
		public function pm_form_email_body_message_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_form_email_body_message_meta = get_post_meta( $post->ID, 'pm_form_email_body_message_meta', true );
			
			?>
            	<p class="form-notice"><?php esc_html_e('The text entered here will appear in the body of the outgoing email. Applies to the "Form" template only.', 'newslettermanagement') ?></p>
                <textarea name="pm_form_email_body_message_meta" class="form-field" rows="30"><?php echo esc_attr($pm_form_email_body_message_meta); ?></textarea>
                
                
            <?php
			
			
		}
		
		
		
		//USER GROUPS
		public function pm_user_groups_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_user_groups_meta = get_post_meta( $post->ID, 'pm_user_groups_meta', true );
			//print_r($pm_user_groups_meta);
			
			global $wpdb;
			
			$group_results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}newsletter_user_groups", ARRAY_A );
			
			?>
            	<p class="form-notice"><?php esc_html_e('Select which User Group(s) the form email should be sent to. Applies to the "Form" template only.', 'newslettermanagement') ?></p>
                
                <?php $counter = 0; ?>
                
                <?php foreach($group_results as $group) { ?>
                
                	<?php if( is_array($pm_user_groups_meta) ) { ?>
                    
                    	<label style="display:block;">
                    		<input name="pm_user_groups_meta[]" type="checkbox" value="<?php echo $group['id'] ?>" <?php echo in_array($group['id'], $pm_user_groups_meta) ? 'checked="checked"' : '' ?> /> <?php echo $group['group_name'] ?> 
                        </label>
                    
                    <?php } else { ?>
                    
                    	<label style="display:block;">
                    		<input name="pm_user_groups_meta[]" type="checkbox" value="<?php echo $group['id'] ?>" /> <?php echo $group['group_name'] ?> 
                        </label>
                    
                    <?php } ?>
                    
                    <?php $counter++; ?>
                
                <?php } ?>
                
            <?php
			
		}
		
		//TRANSLATION BUTTON
		public function pm_show_translation_btn_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_show_translation_btn = get_post_meta( $post->ID, 'pm_show_translation_btn', true );
			
			?>
            
            	<p class="form-notice"><?php esc_html_e('This option only applies if the WPML plugin is installed and active.', 'newslettermanagement') ?></p>
                
                <select name="pm_show_translation_btn">
                	<option value="yes" <?php selected( $pm_show_translation_btn, 'yes' ); ?>>Yes</option>
                    <option value="no" <?php selected( $pm_show_translation_btn, 'no' ); ?>>No</option>
                </select>
                
            <?php
			
			
		}
		
		//SHOW LOGOS
		public function pm_show_logos_btn_meta_function($post) {
		
			//We need this to save our data
			wp_nonce_field( basename( __FILE__ ), 'newsletter_nonce' );
			
			//retrieve the metadata value if it exists
			$pm_show_logos = get_post_meta( $post->ID, 'pm_show_logos', true );
			
			?>
                
                <select name="pm_show_logos">
                	<option value="yes" <?php selected( $pm_show_logos, 'yes' ); ?>>Yes</option>
                    <option value="no" <?php selected( $pm_show_logos, 'no' ); ?>>No</option>
                </select>
                
            <?php
			
			
		}
		
		//CALL TO ACTIONS
		public function pm_call_to_action_boxes_meta_function($post) {
	
			// Use nonce for verification
			wp_nonce_field( 'theme_metabox', 'post_meta_nonce' );
			
			//Retrieve the meta value if it exists
			$pm_enable_slider_system = get_post_meta( $post->ID, 'pm_enable_slider_system', true );	
			$pm_call_to_actions = get_post_meta( $post->ID, 'pm_call_to_actions', true ); //ARRAY VALUE	
			//print_r($pm_call_to_actions);
			
			$newsletter_posts_args = array(
				'post_type' => 'otanewsletters',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'order' => 'DESC',
			);
		
			$newsletter_query = new WP_Query($newsletter_posts_args);
			
			//Use for cloning options
			echo '<ul id="pm_call_to_action_post_url_list" class="hide-select-list">';									
						
				if ($newsletter_query->have_posts()) : while ($newsletter_query->have_posts()) : $newsletter_query->the_post();		
					echo '<li data-url="'. get_the_permalink() .'" data-title="'.  get_the_title() .'"></li>';				
				endwhile; else: endif;	
					
			echo '</ul>';
				
			?>
				
				<p class="form-notice"><?php esc_html_e('Applies to the "Landing Page" template only.', 'newslettermanagement') ?></p>
											
                <div id="pm-call-to-actions-container">
                
                    <?php 
                    
                        $counter = 0;
                    
                        if(is_array($pm_call_to_actions)){
                            
                            //print_r($pm_call_to_action_boxes_entries);
                            
                            foreach($pm_call_to_actions as $val) {
                            
                                echo '<div class="pm-call-to-actions-field-container" id="pm_call_to_actions_field_container_'.$counter.'">';
                                									
									echo '<textarea name="pm_call_to_action_post[]" class="form-field textarea" id="pm_call_to_action_post_'.$counter.'">'.esc_attr($val['message']).'</textarea>';
																	
                                    //echo '<input type="text" value="'.esc_url($val['url']).'" name="pm_call_to_action_post_url[]" id="pm_call_to_action_post_url_'.$counter.'" class="form-field" />';
									
									echo '<select name="pm_call_to_action_post_url[]" id="pm_call_to_action_post_url_'.$counter.'">';
									
										echo '<option value="default">-- Button URL --</option>';
									
										if ($newsletter_query->have_posts()) : while ($newsletter_query->have_posts()) : $newsletter_query->the_post();
										
											$url = get_the_permalink();
											
											echo '<option value="'. $url .'" '. selected( $val['url'], $url ) .'>'. get_the_title() .'</option>';
										
										endwhile; else: endif;
									
									echo '</select>';
									
									
                                    echo '<br /><input type="button" value="'.esc_html__('Remove Entry', 'newslettermanagement').'" class="button button-secondary button-large delete pm-remove-call-to-action-entry-btn" id="pm_call_to_action_post_remove_btn_'.$counter.'" />';
                                
                                echo '</div>';
                                
                                $counter++;
                                
                            }
                            
                        } else {
                        
                            //Default value upon post initialization
                            echo '<b><i>'.esc_html__('No call to actions found.','newslettermanagement').'</i></b> <br><br>';
							
							
                            
                        }                    
                    
                    ?>            
                
                </div>
                
                
                <input type="button" id="pm-add-new-call-to-action-btn" class="button button-primary button-large" value="<?php _e('Create New Call To Action','newslettermanagement') ?>">      
			
			<?php
			
		}
		
				
		//SAVE DATA
		public function save_post_meta( $post_id ) {
			
			//Verify the nonce before proceeding.
			if ( !isset( $_POST['newsletter_nonce'] ) || !wp_verify_nonce( $_POST['newsletter_nonce'], basename( __FILE__ ) ) ) {
				return $post_id;
			}	
			
			//Save Meta options
			if(isset($_POST['newsletter_banner_img'])){
				update_post_meta( $post_id, 'newsletter_banner_img', sanitize_text_field($_POST['newsletter_banner_img']) );
			}
			
			if(isset($_POST['newsletter_banner_msg'])){
				update_post_meta( $post_id, 'newsletter_banner_msg', sanitize_text_field($_POST['newsletter_banner_msg']) );
			}
			
			if(isset($_POST['pm_show_translation_btn'])){
				update_post_meta( $post_id, 'pm_show_translation_btn', sanitize_text_field($_POST['pm_show_translation_btn']) );
			}
			
			if(isset($_POST['pm_show_logos'])){
				update_post_meta( $post_id, 'pm_show_logos', sanitize_text_field($_POST['pm_show_logos']) );
			}
			
			if(isset($_POST['pm_return_to_page_url_meta'])){
				update_post_meta( $post_id, 'pm_return_to_page_url_meta', sanitize_text_field($_POST['pm_return_to_page_url_meta']) );
			}
			
			if(isset($_POST['pm_newsletter_template_meta'])){
				update_post_meta( $post_id, 'pm_newsletter_template_meta', sanitize_text_field($_POST['pm_newsletter_template_meta']) );
			}			
			
			if(isset($_POST['pm_form_message_meta'])){
				update_post_meta( $post_id, 'pm_form_message_meta', sanitize_text_field($_POST['pm_form_message_meta']) );
			}
			
			if(isset($_POST['pm_form_email_body_message_meta'])){
				update_post_meta( $post_id, 'pm_form_email_body_message_meta', sanitize_textarea_field($_POST['pm_form_email_body_message_meta']) );
			}
			
			if(isset($_POST['pm_user_groups_meta'])){
				update_post_meta( $post_id, 'pm_user_groups_meta', $_POST['pm_user_groups_meta'] );
			}
			
			
			
			
			
			if(isset($_POST["pm_call_to_action_post"])){
				
				$messages = array();	
				$urls = array();			
				$counter = 0;
								
				foreach($_POST["pm_call_to_action_post"] as $key => $text_field){
					
					if(!empty($text_field)){
						$messages[$counter] = array('message' => $text_field, 'url' => $_POST["pm_call_to_action_post_url"][$counter]);
					}					
					$counter++;					
				}
							
				//$pm_slider_system_post = $_POST['pm_slider_system_post'];
				update_post_meta($post_id, "pm_call_to_actions", $messages);
				
			} else {
			
				$images = '';			
				update_post_meta($post_id, "pm_call_to_actions", $messages);
				
			}	
			
			
			
								
		}//end of Save Data
		
	}//end of class
	
}//end of class collision if

// Instantiate the class
$newsletterManager = new NewsletterManager; 


?>