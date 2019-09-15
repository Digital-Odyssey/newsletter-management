(function($) {
	
	'use strict';
	
	var isMobile = {
		Android: function() {
			return navigator.userAgent.match(/Android/i);
		},
		BlackBerry: function() {
			return navigator.userAgent.match(/BlackBerry/i);
		},
		iOS: function() {
			return navigator.userAgent.match(/iPhone|iPad|iPod/i);
		},
		Opera: function() {
			return navigator.userAgent.match(/Opera Mini/i);
		},
		Windows: function() {
			return navigator.userAgent.match(/IEMobile/i);
		},
		any: function() {
			return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
		}
	};

	
	$(document).ready(function(e) {
		
		// global
		var Modernizr = window.Modernizr;
		
			
		// support for CSS Transitions & transforms
		var support = Modernizr.csstransitions && Modernizr.csstransforms;
		var support3d = Modernizr.csstransforms3d;
		// transition end event name and transform name
		// transition end event name
		var transEndEventNames = {
								'WebkitTransition' : 'webkitTransitionEnd',
								'MozTransition' : 'transitionend',
								'OTransition' : 'oTransitionEnd',
								'msTransition' : 'MSTransitionEnd',
								'transition' : 'transitionend'
							},
		transformNames = {
						'WebkitTransform' : '-webkit-transform',
						'MozTransform' : '-moz-transform',
						'OTransform' : '-o-transform',
						'msTransform' : '-ms-transform',
						'transform' : 'transform'
					};
					
		if( support ) {
			this.transEndEventName = transEndEventNames[ Modernizr.prefixed( 'transition' ) ] + '.PMMain';
			this.transformName = transformNames[ Modernizr.prefixed( 'transform' ) ];
			//console.log('this.transformName = ' + this.transformName);
		}
		

	/* ==========================================================================
	   On ready calls
	   ========================================================================== */
		
		//Check parallax effect for desktop
		methods.checkParallax();
		
		//Bind event listener
		$(window).resize(methods.windowResize);
		
	/* ==========================================================================
	   Window resize call - comment out if not required
	   ========================================================================== */
		$(window).resize(function(e) {
			methods.windowResize();
		});
		
	/* ==========================================================================
	   Window scroll - comment out if not required
	   ========================================================================== */
		$(window).scroll(function (e) {
			methods.windowScroll(e);
		});
		
	/* ==========================================================================
	   Window load calls - comment out if not required
	   ========================================================================== */
	   $(window).load(function (e) {
			
			/*if( $(window).width() > 1199 ) {
				if( $('.pm-hero-banner-container').length > 0 ) {
					$('.pm-hero-banner-container').height($(window).height());
				}
			}*/
			
		});
		
	/* ==========================================================================
	   Back to top button
	   ========================================================================== */
		$('#pm-back-top').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});
	   
	
	/* ==========================================================================
	   Subscribe modal / Mailchimp Widget
	   ========================================================================== */
	   /*if( $('#pm-subscribe-me').length > 0 ){
			
			var cookieName = 'newsletter_closed',
			checkCookie = methods.checkCookie(cookieName); 
			
			if(!checkCookie) {
				
				$("#pm-subscribe-me").subscribeBetter({
				  trigger: "onidle",  //onidle, onload, atendpage
				  animation: "fade", //fade, flyInLeft, flyInRight, flyInUp, flyInDown
				  delay: 1500,
				  showOnce: true,
				  autoClose: false,
				  scrollableModal: false
				});
				
				methods.ajaxMailChimpForm($("#pm-subscribe-form"), $("#pm-subscribe-result"));
				
				//block newsletter popup for 5 days 
				methods.setCookie(cookieName, 'blocked', 5);
								
			}


		}*/

		
		/*if( $('#pm-subscribe-form').length > 0 ){
			methods.ajaxMailChimpForm($("#pm-subscribe-form"), $("#pm-subscribe-result"));
		}*/
	
		
	/* ==========================================================================
	   Initialize WOW plugin for element animations
	   ========================================================================== */
		if( $(window).width() > 991 ){
			
			if( typeof WOW != 'undefined'  ){	
				new WOW().init();
			}
			
		}
	
	   
	/* ==========================================================================
	   Detect page scrolls on buttons
	   ========================================================================== */
		if( $('.pm-page-scroll').length > 0 ){
			
			$('.pm-page-scroll').click(function(e){
								
				e.preventDefault();
				var $this = $(e.target);
				var sectionID = $this.attr('href');
				
				
				$('html, body').animate({
					scrollTop: $(sectionID).offset().top
				}, 1000);
				
			});
			
		}
		 
			
	}); //end of document ready
	
	
	/* ==========================================================================
	   Options (store global variables if required)
	   ========================================================================== */
		var options = {
			parallaxMode : false,
			pagePercentage : 0
		}
	
	/* ==========================================================================
	   Methods (store global methods)
	   ========================================================================== */
		var methods = {
											
			windowResize : function() {
				//resize calls
				
				methods.checkParallax();
								
			},
			
			windowScroll : function(e) {
				
				methods.runParallax();	
				
				//scroll calls
				
				//Calculate window scroll status
				/*if($(window).width() > 1200){
					
					//Add hero banner fade effect
					var base = e.target,
					container = $(base);
					
					var wrapper = $('#pm_layout_wrapper'),
					viewportHeight = $(window).height(), 
					scrollbarHeight = viewportHeight / wrapper.height() * viewportHeight,
					progress = $(window).scrollTop() / (wrapper.height() - viewportHeight),
					distance = progress * (viewportHeight - scrollbarHeight) + scrollbarHeight / 2 - container.height() / 2;
					
					//$('#back-top-status').text(Math.round(progress * 100) + '%');
					//$('#back-top-status').text(Math.round(progress * 100));
					
					//track this for global purposes
					options.pagePercentage = Math.round(progress * 100);
					
					$('#pm-pattern-overlay').css({
						'opacity' : (options.pagePercentage / 100) * 8
					});
					
					$('.pm-hero-banner-fade').css({
						'bottom' : 	-options.pagePercentage * 8,
						'opacity' : 1 - ((options.pagePercentage / 100) * 8)
					});
					
					//console.log( (options.pagePercentage / 100) * 8 );
						
				}*/ //end if
				
								
			},
						
			isTouchDevice : function() {
				return !!('ontouchstart' in window) || ( !! ('onmsgesturechange' in window) && !! window.navigator.maxTouchPoints);
			},
			
			ajaxMailChimpForm : function($form, $resultElement) {
				
				$form.submit(function(e) {
					
					e.preventDefault();
					
					/*$resultElement.css("color", "black");
					$resultElement.html("Subscribing...");
					methods.submitSubscribeForm($form, $resultElement);*/
					
					if (!methods.isValidEmail($form)) {
						var error = 'A valid email address must be provided.';
						$resultElement.html(error);
						$resultElement.css("color", "red");
					} else {
						$resultElement.css("color", "black");
						$resultElement.html("Subscribing...");
						methods.submitSubscribeForm($form, $resultElement);
					}
				});
				
			},
			
			isValidEmail : function($form) {
				// If email is empty, show error message.
				// contains just one @
				var email = $form.find("input[type='email']").val();
				if (!email || !email.length) {
					return false;
				} else if (email.indexOf("@") == -1) {
					return false;
				}
				return true;
			},
			
			submitSubscribeForm : function($form, $resultElement) {
				
				$.ajax({
					type: "GET",
					url: $form.attr("action"),
					data: $form.serialize(),
					cache: false,
					dataType: "jsonp",
					jsonp: "c", // trigger MailChimp to return a JSONP response
					contentType: "application/json; charset=utf-8",
					error: function(error){
						// According to jquery docs, this is never called for cross-domain JSONP requests
					},
					success: function(data){
						if (data.result != "success") {
							var message = data.msg || 'Sorry, unable to subscribe. Please try again later.';
							$resultElement.css("color", "red");
							if (data.msg && data.msg.indexOf("already subscribed") >= 0) {
								message = "You're already subscribed. Thank you.";
								$resultElement.css("color", "black");
							}
							$resultElement.html(message);
						} else {
							$resultElement.css("color", "black");
							$resultElement.html('Thank you!<br>You must confirm the subscription in your inbox.');
						}
					}
				});
				
			},
			
			checkParallax : function() {
				
				
				
				/*var $window = $(window),
				$windowsize = $window.width();
				
				if ($windowsize < 1200) {
					
					//if the window is less than 980px, destroy parallax...
					if(options.parallaxMode) {
						$.stellar('destroy');
						options.parallaxMode = false;
						//console.log('destroy parallax once');
					}
					
					
				} else {
					
					if(!options.parallaxMode) {
						options.parallaxMode = true;
						
						//console.log('run parallax once');
					}
										
				}*/
				
			},
			
			runParallax : function() {
			
				var scrolled = $(window).scrollTop();
				$('.hero').css('top',-(scrolled*0.0315)+'rem');
				$('.hero > h1').css('top',-(scrolled*-0.005)+'rem');
				$('.hero > h1').css('opacity',1-(scrolled*.00125));
				$('.pm-hero-banner-btn').css('opacity',1-(scrolled*.00125));
				
				
			}
			
			/*runParallax : function() {
			
				//enforce check to make sure we are not on a mobile device
				if( !isMobile.any()){
								
					//stellar parallax
					$.stellar({
					  horizontalOffset: 0,
					  verticalOffset: 0,
					  horizontalScrolling: false,
					});
					
					$('.pm-parallax-panel').stellar();
					
									
				}
				
			}*///end of function
			
		};
		
	
	
})(jQuery);

